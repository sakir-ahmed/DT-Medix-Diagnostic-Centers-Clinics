<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\Refund;
use App\Services\ApprovalService;
use App\Services\Billing\InvoiceCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Approves or rejects a refund. Approval records it as cash immediately,
 * capped at the amount still overpaid.
 */
final class DecideRefundAction
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly InvoiceCalculator $calculator,
    ) {}

    public function execute(Refund $refund, bool $approve, ?string $login, ?string $pin): Refund
    {
        return DB::transaction(function () use ($refund, $approve, $login, $pin): Refund {
            /** @var Refund $refund */
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if ($refund->status !== ApprovalStatus::Pending) {
                throw DomainException::make('INVALID_STATE', 409);
            }

            $approver = $this->approvals->verify($login, $pin, 'refunds.approve', 'refund-decision:'.$refund->id);

            if (! $approve) {
                $refund->forceFill([
                    'status' => ApprovalStatus::Rejected,
                    'decided_by' => $approver->id,
                    'decided_at' => now(),
                ])->save();

                activity('billing')->causedBy($approver)->performedOn($refund)->withProperties([
                    'invoice_id' => $refund->invoice_id,
                ])->log('refund.rejected');

                return $refund->refresh();
            }

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($refund->invoice_id)->lockForUpdate()->firstOrFail();
            $invoice = $this->calculator->recalculate($invoice);
            $cap = InvoiceCalculator::overpaid($invoice);

            if (Money::compare($cap, '0') <= 0) {
                throw DomainException::make('REFUND_EXCEEDS_OVERPAID', 422);
            }

            $refund->forceFill([
                'amount' => Money::min((string) $refund->amount, $cap),
                'method' => PaymentMethod::Cash->value,
                'status' => ApprovalStatus::Approved,
                'decided_by' => $approver->id,
                'decided_at' => now(),
            ])->save();

            $this->calculator->recalculate($invoice);

            activity('billing')->causedBy($approver)->performedOn($refund)->withProperties([
                'invoice_id' => $invoice->id,
                'amount' => (string) $refund->amount,
            ])->log('refund.approved');

            return $refund->refresh();
        });
    }
}
