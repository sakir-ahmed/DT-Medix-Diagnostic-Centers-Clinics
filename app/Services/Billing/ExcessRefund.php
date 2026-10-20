<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Refund;
use App\Support\Money;

/**
 * Opens one pending cash refund for money paid beyond the current total.
 * Pending refunds already opened are not opened again.
 */
final class ExcessRefund
{
    public function open(Invoice $invoice, string $reason, ?int $requestedBy): ?Refund
    {
        $pending = Money::add('0', ...$invoice->refunds()
            ->where('status', ApprovalStatus::Pending->value)
            ->pluck('amount')
            ->map(fn (mixed $amount): string => (string) $amount)
            ->all());

        $gap = Money::sub(InvoiceCalculator::overpaid($invoice), $pending);

        if (Money::compare($gap, '0') <= 0) {
            return null;
        }

        return Refund::query()->create([
            'branch_id' => $invoice->branch_id,
            'invoice_id' => $invoice->id,
            'amount' => $gap,
            'method' => PaymentMethod::Cash->value,
            'reason' => $reason,
            'status' => ApprovalStatus::Pending,
            'requested_by' => $requestedBy,
        ]);
    }
}
