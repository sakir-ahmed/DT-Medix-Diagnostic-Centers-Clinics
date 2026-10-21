<?php

declare(strict_types=1);

use App\Actions\Billing\CancelItemsAction;
use App\Actions\Billing\CollectPaymentAction;
use App\Actions\Billing\CreateOrderAction;
use App\Actions\Billing\DecideDiscountAction;
use App\Actions\Billing\DecideRefundAction;
use App\Actions\Billing\RequestRefundAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Center\SeedCenterDefaultsAction;
use App\Enums\ApprovalStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\RoleName;
use App\Enums\SampleStatus;
use App\Exceptions\DomainException;
use App\Models\Branch;
use App\Models\BranchTestPrice;
use App\Models\Department;
use App\Models\DiscountRequest;
use App\Models\Order;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Test;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    makeCenter();
    value(fn () => app(SeedCenterDefaultsAction::class)->execute());
    $this->branch = mainBranch();
    $this->department = value(fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $this->receptionist = makeUser(RoleName::Receptionist);
    $this->manager = makeUser(RoleName::BranchManager, attributes: [
        'username' => 'counter-manager',
        'approval_pin' => '2468',
    ]);
    $this->patient = value(fn () => Patient::factory()->create(['name' => 'Rahim Uddin', 'preferred_locale' => 'en']));
});

/**
 * @param  array<string, mixed>  $overrides
 */
function catalogLine(int $departmentId, string $code, string $price, array $overrides = []): Test
{
    return app(SaveTestAction::class)->execute($overrides + [
        'code' => $code,
        'name' => $code.' test',
        'department_id' => $departmentId,
        'price' => $price,
        'tat_hours' => 4,
        'sample_type' => 'blood',
        'container' => 'edta',
    ]);
}

/**
 * @param  array<string, mixed>  $data
 */
function placeOrder(Branch $branch, User $actor, array $data): Order
{
    return app(CreateOrderAction::class)->execute($branch, $data, $actor);
}

it('snapshots the price, numbers the invoice and calculates a partial due', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '500');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'payments' => [['amount' => '200.00']],
        ]);
    });

    $invoice = $order->invoice;
    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_no)->toBe('INV-BR01-'.now()->format('y').'-000001')
        ->and($invoice->subtotal)->toBe('500.00')
        ->and($invoice->total)->toBe('500.00')
        ->and($invoice->paid)->toBe('200.00')
        ->and($invoice->due)->toBe('300.00')
        ->and($invoice->status)->toBe(InvoiceStatus::Partial)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items[0]->price)->toBe('500.00')
        ->and($order->samples)->toHaveCount(1)
        ->and($order->samples[0]->status)->toBe(SampleStatus::Pending)
        ->and($order->samples[0]->sample_code)->toBe('BR01-'.now()->format('ymd').'-0001');

    $test = value(fn () => Test::query()->where('code', 'CBC')->firstOrFail());
    $test->update(['price' => '900']);
    expect($order->items[0]->fresh()->price)->toBe('500.00');

    $receipts = value(fn () => Payment::query()->pluck('receipt_no')->all());
    expect($receipts)->toBe(['MR-BR01-'.now()->format('y').'-000001']);

    $logged = value(fn () => Activity::query()->where('log_name', 'billing')->get()->toJson());
    expect($logged)->not->toContain('Rahim');
});

it('uses the branch price and keeps later catalog changes off the invoice', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'LFT', '400');
        BranchTestPrice::query()->create(['branch_id' => $this->branch->id, 'test_id' => $test->id, 'price' => '350']);

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
        ]);
    });

    expect($order->items[0]->price)->toBe('350.00')
        ->and($order->invoice->due)->toBe('350.00')
        ->and($order->invoice->status)->toBe(InvoiceStatus::Due);
});

it('splits a package across its tests and drops a standalone duplicate', function (): void {
    $order = value(function () {
        $cbc = catalogLine($this->department->id, 'CBC', '600');
        $lft = catalogLine($this->department->id, 'LFT', '500');
        $cbc->update(['sort_order' => 1]);
        $lft->update(['sort_order' => 2]);
        $package = Package::query()->create(['code' => 'PANEL', 'name' => 'Panel', 'price' => '1000']);
        $package->tests()->sync([$cbc->id, $lft->id]);

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$cbc->id],
            'package_ids' => [$package->id],
        ]);
    });

    expect($order->items)->toHaveCount(2)
        ->and($order->items->pluck('code')->all())->toBe(['CBC', 'LFT'])
        ->and($order->items[0]->price)->toBe('545.45')
        ->and($order->items[1]->price)->toBe('454.55')
        ->and($order->invoice->subtotal)->toBe('1000.00')
        ->and($order->samples)->toHaveCount(1);
});

it('groups samples by type and container', function (): void {
    $order = value(function () {
        $cbc = catalogLine($this->department->id, 'CBC', '100');
        $esr = catalogLine($this->department->id, 'ESR', '80');
        $urine = catalogLine($this->department->id, 'URN', '50', ['sample_type' => 'urine', 'container' => 'urine_cup']);

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$cbc->id, $esr->id, $urine->id],
        ]);
    });

    expect($order->samples)->toHaveCount(2)
        ->and($order->items->where('code', 'URN')->first()->sample_id)->not->toBe($order->items->where('code', 'CBC')->first()->sample_id)
        ->and($order->items->where('code', 'ESR')->first()->sample_id)->toBe($order->items->where('code', 'CBC')->first()->sample_id);
});

it('applies a discount inside the limit and holds one above it', function (): void {
    $within = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '1000');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_type' => 'percent',
            'discount_value' => '10',
        ]);
    });

    expect($within->invoice->discount_amount)->toBe('100.00')
        ->and($within->invoice->total)->toBe('900.00')
        ->and($within->invoice->due)->toBe('900.00');

    $held = value(function () {
        $test = Test::query()->where('code', 'CBC')->firstOrFail();

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_type' => 'percent',
            'discount_value' => '25',
            'discount_reason' => 'Staff request',
        ]);
    });

    expect($held->invoice->discount_amount)->toBe('0.00')
        ->and($held->invoice->total)->toBe('1000.00')
        ->and(value(fn () => DiscountRequest::query()->where('invoice_id', $held->invoice->id)->first()))
        ->status->toBe(ApprovalStatus::Pending)
        ->discount_value->toBe('25.00');
});

it('refuses an over-limit discount when the client insists and saves nothing', function (): void {
    $caught = null;

    try {
        value(function () {
            $test = catalogLine($this->department->id, 'CBC', '1000');

            return placeOrder($this->branch, $this->receptionist, [
                'patient_id' => $this->patient->id,
                'test_ids' => [$test->id],
                'discount_type' => 'percent',
                'discount_value' => '25',
                'discount_reason' => 'Staff request',
                'insist_discount' => true,
            ]);
        });
    } catch (DomainException $exception) {
        $caught = $exception;
    }

    expect($caught?->errorCode)->toBe('DISCOUNT_LIMIT_EXCEEDED')
        ->and(value(fn () => Order::query()->count()))->toBe(0);
});

it('applies an over-limit discount for a manager or a manager pin', function (): void {
    $byRole = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '200');

        return placeOrder($this->branch, $this->manager, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_type' => 'fixed',
            'discount_value' => '50',
            'discount_reason' => 'Charity case',
        ]);
    });

    expect($byRole->invoice->total)->toBe('150.00')
        ->and($byRole->invoice->discount_approved_by)->toBe($this->manager->id);

    $byPin = value(function () {
        $test = Test::query()->where('code', 'CBC')->firstOrFail();

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_type' => 'fixed',
            'discount_value' => '50',
            'discount_reason' => 'Charity case',
            'approval_login' => 'counter-manager',
            'approval_pin' => '2468',
        ]);
    });

    expect($byPin->invoice->total)->toBe('150.00')
        ->and($byPin->invoice->discount_approved_by)->toBe($this->manager->id);
});

it('requires a reason for referrer and free discounts', function (string $category): void {
    $caught = null;

    try {
        value(function () use ($category) {
            $test = catalogLine($this->department->id, 'CBC', '100');

            return placeOrder($this->branch, $this->manager, [
                'patient_id' => $this->patient->id,
                'test_ids' => [$test->id],
                'discount_type' => 'percent',
                'discount_value' => '5',
                'discount_category' => $category,
            ]);
        });
    } catch (DomainException $exception) {
        $caught = $exception;
    }

    expect($caught?->errorCode)->toBe('REASON_REQUIRED');
})->with(['referrer', 'free']);

it('treats a free case as 100 percent and holds it until approval', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '100');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_category' => 'free',
            'discount_reason' => 'Charity',
        ]);
    });

    $request = value(fn () => DiscountRequest::query()->firstOrFail());

    expect($order->invoice->total)->toBe('100.00')
        ->and($request->discount_type)->toBe('percent')
        ->and($request->discount_value)->toBe('100.00');
});

it('refuses a payment above the due and a payment on a settled invoice', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '100');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'payments' => [['amount' => '40'], ['amount' => '30']],
        ]);
    });

    expect($order->invoice->paid)->toBe('70.00')
        ->and($order->invoice->due)->toBe('30.00')
        ->and(value(fn () => Payment::query()->count()))->toBe(2);

    $exceeded = null;

    try {
        value(fn () => app(CollectPaymentAction::class)->execute($order->invoice, [['amount' => '40']]));
    } catch (DomainException $exception) {
        $exceeded = $exception;
    }

    expect($exceeded?->errorCode)->toBe('PAYMENT_EXCEEDS_DUE')
        ->and($order->invoice->fresh()->paid)->toBe('70.00');

    value(fn () => app(CollectPaymentAction::class)->execute($order->invoice->fresh(), [['amount' => '30']]));
    expect($order->invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($order->invoice->fresh()->due)->toBe('0.00');

    $settled = null;

    try {
        value(fn () => app(CollectPaymentAction::class)->execute($order->invoice->fresh(), [['amount' => '1']]));
    } catch (DomainException $exception) {
        $settled = $exception;
    }

    expect($settled?->errorCode)->toBe('INVOICE_ALREADY_PAID');
});

it('adds vat only when the center has it enabled', function (): void {
    settings()->set(['vat_enabled' => true, 'vat_percent' => 15]);

    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '200');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
        ]);
    });

    expect($order->invoice->vat_amount)->toBe('30.00')
        ->and($order->invoice->total)->toBe('230.00')
        ->and($order->invoice->due)->toBe('230.00');
});

it('cancels an uncollected line and opens a refund for cash already taken', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '100');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'payments' => [['amount' => '100']],
        ]);
    });

    $order = value(fn () => app(CancelItemsAction::class)->execute($order, [$order->items[0]->id], 'Wrong test', requestedBy: $this->receptionist->id));

    expect($order->status)->toBe('cancelled')
        ->and($order->items[0]->status)->toBe(OrderItemStatus::Cancelled)
        ->and($order->invoice->total)->toBe('0.00')
        ->and($order->invoice->status)->toBe(InvoiceStatus::Cancelled);

    $refund = value(fn () => Refund::query()->firstOrFail());
    expect($refund->amount)->toBe('100.00')->and($refund->status)->toBe(ApprovalStatus::Pending);

    $refund = value(fn () => app(DecideRefundAction::class)->execute($refund, true, 'counter-manager', '2468'));

    expect($refund->status)->toBe(ApprovalStatus::Approved)
        ->and($refund->method)->toBe('cash')
        ->and($order->invoice->fresh()->refunded)->toBe('100.00')
        ->and($order->invoice->fresh()->due)->toBe('0.00')
        ->and($order->invoice->fresh()->status)->toBe(InvoiceStatus::Refunded);
});

it('refuses to cancel a collected line unless the center allows a refund and a manager approves', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '80');
        $placed = placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
        ]);
        $placed->items[0]->forceFill(['status' => OrderItemStatus::Collected])->save();

        return $placed->fresh('items');
    });

    $blocked = null;

    try {
        value(fn () => app(CancelItemsAction::class)->execute($order, [$order->items[0]->id], 'Patient left', 'counter-manager', '2468'));
    } catch (DomainException $exception) {
        $blocked = $exception;
    }

    expect($blocked?->errorCode)->toBe('COLLECTED_NOT_REFUNDABLE')
        ->and($order->items[0]->fresh()->status)->toBe(OrderItemStatus::Collected);

    $settings = settings()->rules();
    $settings['collected_samples_refundable'] = true;
    settings()->set($settings);

    $order = value(fn () => app(CancelItemsAction::class)->execute($order, [$order->items[0]->id], 'Patient left', 'counter-manager', '2468'));

    expect($order->items[0]->status)->toBe(OrderItemStatus::Cancelled)
        ->and($order->invoice->status)->toBe(InvoiceStatus::Cancelled);
});

it('applies a held discount after payment and refunds only the excess', function (): void {
    $order = value(function () {
        $test = catalogLine($this->department->id, 'CBC', '100');

        return placeOrder($this->branch, $this->receptionist, [
            'patient_id' => $this->patient->id,
            'test_ids' => [$test->id],
            'discount_type' => 'percent',
            'discount_value' => '20',
            'discount_reason' => 'Regular patient',
            'payments' => [['amount' => '100']],
        ]);
    });

    $request = value(fn () => DiscountRequest::query()->firstOrFail());
    value(fn () => app(DecideDiscountAction::class)->execute($request, true, 'counter-manager', '2468'));

    $invoice = $order->invoice->fresh();
    $refund = value(fn () => Refund::query()->firstOrFail());

    expect($invoice->total)->toBe('80.00')
        ->and($invoice->paid)->toBe('100.00')
        ->and($invoice->due)->toBe('0.00')
        ->and($refund->amount)->toBe('20.00')
        ->and($refund->status)->toBe(ApprovalStatus::Pending);

    $tooMuch = null;

    try {
        value(fn () => app(RequestRefundAction::class)->execute($invoice, '20.01', 'Extra', $this->receptionist->id));
    } catch (DomainException $exception) {
        $tooMuch = $exception;
    }

    expect($tooMuch?->errorCode)->toBe('REFUND_EXCEEDS_OVERPAID');

    value(fn () => app(DecideRefundAction::class)->execute($refund, true, 'counter-manager', '2468'));

    expect($invoice->fresh()->refunded)->toBe('20.00')
        ->and($invoice->fresh()->due)->toBe('0.00')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});
