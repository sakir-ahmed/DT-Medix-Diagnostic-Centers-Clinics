<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\Refund;
use App\Services\Billing\InvoiceCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Asks for a cash refund up to the amount paid beyond the invoice total.
 */
final class RequestRefundAction
{
    public function __construct(private readonly InvoiceCalculator $calculator) {}

    public function execute(Invoice $invoice, string $amount, string $reason, ?int $requestedBy = null): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw DomainException::make('REASON_REQUIRED', 422);
        }

        return DB::transaction(function () use ($invoice, $amount, $reason, $requestedBy): Refund {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $invoice = $this->calculator->recalculate($invoice);

            $pending = Money::add('0', ...$invoice->refunds()
                ->where('status', ApprovalStatus::Pending->value)
                ->pluck('amount')
                ->map(fn (mixed $value): string => (string) $value)
                ->all());

            $available = Money::sub(InvoiceCalculator::overpaid($invoice), $pending);
            $amount = Money::of($amount);

            if (Money::compare($amount, '0') <= 0 || Money::compare($amount, $available) > 0) {
                throw DomainException::make('REFUND_EXCEEDS_OVERPAID', 422);
            }

            $refund = Refund::query()->create([
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => PaymentMethod::Cash->value,
                'reason' => $reason,
                'status' => ApprovalStatus::Pending,
                'requested_by' => $requestedBy,
            ]);

            activity('billing')->performedOn($refund)->withProperties([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ])->log('refund.requested');

            return $refund;
        });
    }
}
