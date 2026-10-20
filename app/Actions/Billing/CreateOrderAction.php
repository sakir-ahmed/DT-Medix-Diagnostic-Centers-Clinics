<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\ApprovalStatus;
use App\Exceptions\DomainException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\Test;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\Billing\DiscountGuard;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceCalculator;
use App\Services\Catalog\PriceResolver;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Creates a test order, its invoice, pending samples and any cash taken
 * at the counter, in one transaction. An over-limit discount is applied
 * only with permission or a manager PIN; otherwise it waits as a request
 * and the invoice stays at list price.
 */
final class CreateOrderAction
{
    public function __construct(
        private readonly PriceResolver $prices,
        private readonly DiscountGuard $discounts,
        private readonly DocumentNumbers $numbers,
        private readonly InvoiceCalculator $calculator,
        private readonly CollectPaymentAction $payments,
        private readonly ApprovalService $approvals,
    ) {}

    /**
     * @param  array{
     *     patient_id: int,
     *     test_ids?: list<int>,
     *     package_ids?: list<int>,
     *     appointment_id?: int|null,
     *     referrer_id?: int|null,
     *     urgency?: string,
     *     notes?: string|null,
     *     discount_type?: string|null,
     *     discount_value?: string|null,
     *     discount_category?: string|null,
     *     discount_reason?: string|null,
     *     insist_discount?: bool,
     *     approval_login?: string|null,
     *     approval_pin?: string|null,
     *     payments?: list<array{amount: string, method?: string}>
     * }  $data
     */
    public function execute(Branch $branch, array $data, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($branch, $data, $actor): Order {
            $patient = Patient::query()->whereKey($data['patient_id'])->firstOrFail();
            $this->assertAppointment($data['appointment_id'] ?? null, $patient, $branch);

            $lines = $this->lines($data['test_ids'] ?? [], $data['package_ids'] ?? [], $branch->id);

            if ($lines === []) {
                throw DomainException::make('NOT_FOUND', 404);
            }

            $subtotal = Money::add(...array_map(fn (array $line): string => $line['price'], $lines));
            $category = $data['discount_category'] ?? 'standard';
            $reason = trim((string) ($data['discount_reason'] ?? ''));
            $assessed = $this->discounts->assess($data['discount_type'] ?? null, $data['discount_value'] ?? null, $category, $subtotal);

            if (in_array($category, ['referrer', 'free'], true) && $assessed !== null && $reason === '') {
                throw DomainException::make('REASON_REQUIRED', 422);
            }

            $approvedBy = null;
            $pending = false;

            if ($assessed !== null && $assessed['exceeds']) {
                if ($reason === '') {
                    throw DomainException::make('REASON_REQUIRED', 422);
                }

                if ($actor?->can('discounts.approve')) {
                    $approvedBy = $actor->id;
                } elseif (($data['approval_login'] ?? null) !== null && ($data['approval_login'] ?? '') !== '') {
                    $approvedBy = $this->approvals->verify($data['approval_login'], $data['approval_pin'] ?? null, 'discounts.approve', 'order-discount:'.($actor->id ?? 0))->id;
                } elseif ($data['insist_discount'] ?? false) {
                    throw DomainException::make('DISCOUNT_LIMIT_EXCEEDED', 422);
                } else {
                    $pending = true;
                }
            }

            $apply = $assessed !== null && ! $pending;

            $order = Order::query()->create([
                'branch_id' => $branch->id,
                'patient_id' => $patient->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'referrer_id' => $data['referrer_id'] ?? null,
                'urgency' => ($data['urgency'] ?? 'routine') === 'urgent' ? 'urgent' : 'routine',
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $sampleIds = $this->samples($order, $patient, $branch, $lines);

            foreach ($lines as $index => $line) {
                /** @var Test $test */
                $test = $line['test'];
                $name = $patient->preferred_locale === 'bn' && filled($test->name_bn) ? $test->name_bn : $test->name;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'test_id' => $test->id,
                    'package_id' => $line['package_id'],
                    'department_id' => $test->department_id,
                    'sample_id' => $sampleIds[$index],
                    'code' => $test->code,
                    'name' => $name,
                    'price' => $line['price'],
                    'net_amount' => $line['price'],
                    'vat_applicable' => $test->vat_applicable,
                    'tat_hours' => $test->tat_hours,
                    'expected_at' => now()->addHours($test->tat_hours),
                    'status' => 'ordered',
                ]);
            }

            $invoice = Invoice::query()->create([
                'branch_id' => $branch->id,
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'invoice_no' => $this->numbers->invoice($branch),
                'discount_type' => $apply && $assessed !== null ? $assessed['type'] : null,
                'discount_value' => $apply && $assessed !== null ? $assessed['value'] : '0',
                'discount_reason' => $apply && $reason !== '' ? $reason : null,
            ]);

            if ($approvedBy !== null) {
                $invoice->forceFill(['discount_approved_by' => $approvedBy])->save();
            }

            $invoice = $this->calculator->recalculate($invoice);

            if ($pending && $assessed !== null) {
                $request = DiscountRequest::query()->create([
                    'branch_id' => $branch->id,
                    'invoice_id' => $invoice->id,
                    'discount_type' => $assessed['type'],
                    'discount_value' => $assessed['value'],
                    'reason' => $reason,
                    'status' => ApprovalStatus::Pending,
                    'requested_by' => $actor?->id,
                ]);

                $this->log($actor, $request, 'discount.requested', [
                    'invoice_id' => $invoice->id,
                    'discount_type' => $assessed['type'],
                    'discount_value' => $assessed['value'],
                ]);
            } elseif ($apply && $assessed !== null) {
                $this->log($actor, $invoice, 'discount.applied', [
                    'invoice_id' => $invoice->id,
                    'discount_type' => $assessed['type'],
                    'discount_value' => $assessed['value'],
                    'approved_by' => $approvedBy,
                ]);
            }

            $this->payments->execute($invoice, $data['payments'] ?? [], $actor?->id);

            $this->log($actor, $order, 'order.created', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
            ]);

            return $order->load('invoice', 'items', 'samples');
        });
    }

    private function assertAppointment(?int $appointmentId, Patient $patient, Branch $branch): void
    {
        if ($appointmentId === null) {
            return;
        }

        $appointment = Appointment::query()->whereKey($appointmentId)->where('branch_id', $branch->id)->first();

        if ($appointment === null || $appointment->patient_id !== $patient->id) {
            throw DomainException::make('INVALID_STATE', 422);
        }
    }

    /**
     * Package lines are priced first, so a test that is also selected on
     * its own keeps the package share and appears once.
     *
     * @param  list<int>  $testIds
     * @param  list<int>  $packageIds
     * @return list<array{test: Test, price: string, package_id: int|null}>
     */
    private function lines(array $testIds, array $packageIds, int $branchId): array
    {
        /** @var array<int, true> $included */
        $included = [];
        /** @var list<array{test: Test, price: string, package_id: int|null}> $lines */
        $lines = [];

        $packages = Package::query()->with('tests')->whereIn('id', $packageIds)->where('is_active', true)->get()
            ->sortBy(function (Package $package) use ($packageIds): int {
                $index = array_search($package->id, $packageIds, true);

                return $index === false ? PHP_INT_MAX : $index;
            })
            ->values();

        foreach ($packages as $package) {
            $tests = $package->tests->filter(fn (Test $test): bool => $test->is_active && ! isset($included[$test->id]))->values();

            if ($tests->isEmpty()) {
                continue;
            }

            $weights = $tests->map(fn (Test $test): string => $this->prices->forTest($test, $branchId))->all();
            $shares = $this->shares((string) $package->price, $weights);

            foreach ($tests as $index => $test) {
                $included[$test->id] = true;
                $lines[] = ['test' => $test, 'price' => $shares[$index], 'package_id' => $package->id];
            }
        }

        $tests = Test::query()->whereIn('id', $testIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($testIds as $testId) {
            if (isset($included[$testId]) || ! $tests->has($testId)) {
                continue;
            }

            /** @var Test $test */
            $test = $tests->get($testId);
            $included[$testId] = true;
            $lines[] = ['test' => $test, 'price' => $this->prices->forTest($test, $branchId), 'package_id' => null];
        }

        return $lines;
    }

    /**
     * Spreads $total across weights. The last part absorbs the remainder
     * so the parts add up to $total exactly.
     *
     * @param  list<string>  $weights
     * @return list<string>
     */
    private function shares(string $total, array $weights): array
    {
        $basis = Money::of($total);
        $weightSum = Money::add(...$weights);
        $remaining = $basis;
        $last = count($weights) - 1;
        $shares = [];

        foreach ($weights as $index => $weight) {
            if ($index === $last) {
                $share = $remaining;
            } elseif (Money::isZero($weightSum)) {
                $share = '0.00';
            } else {
                $share = Money::min(Money::of(bcdiv(bcmul(Money::of($weight), $basis, 6), $weightSum, 6)), $remaining);
            }

            $shares[] = $share;
            $remaining = Money::sub($remaining, $share);
        }

        return $shares;
    }

    /**
     * @param  list<array{test: Test, price: string, package_id: int|null}>  $lines
     * @return array<int, int>
     */
    private function samples(Order $order, Patient $patient, Branch $branch, array $lines): array
    {
        /** @var array<string, int> $byGroup */
        $byGroup = [];
        /** @var array<int, int> $ids */
        $ids = [];

        foreach ($lines as $index => $line) {
            $test = $line['test'];
            $key = ($test->sample_type ?? '').'|'.($test->container ?? '');

            if (! isset($byGroup[$key])) {
                $sample = Sample::query()->create([
                    'branch_id' => $branch->id,
                    'order_id' => $order->id,
                    'patient_id' => $patient->id,
                    'sample_code' => $this->numbers->sample($branch),
                    'sample_type' => $test->sample_type,
                    'container' => $test->container,
                    'status' => 'pending',
                ]);
                $byGroup[$key] = $sample->id;
            }

            $ids[$index] = $byGroup[$key];
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(?User $actor, Model $subject, string $event, array $properties): void
    {
        $logger = activity('billing')->performedOn($subject)->withProperties($properties);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log($event);
    }
}
