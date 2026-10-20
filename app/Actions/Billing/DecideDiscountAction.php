<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\ApprovalStatus;
use App\Exceptions\DomainException;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Services\ApprovalService;
use App\Services\Billing\ExcessRefund;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Approves or rejects a pending discount. Approval applies it to the
 * invoice and, when cash was already taken, opens a refund for the excess.
 */
final class DecideDiscountAction
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly InvoiceCalculator $calculator,
        private readonly ExcessRefund $excess,
    ) {}

    public function execute(DiscountRequest $request, bool $approve, ?string $login, ?string $pin): DiscountRequest
    {
        return DB::transaction(function () use ($request, $approve, $login, $pin): DiscountRequest {
            /** @var DiscountRequest $request */
            $request = DiscountRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->status !== ApprovalStatus::Pending) {
                throw DomainException::make('INVALID_STATE', 409);
            }

            $approver = $this->approvals->verify($login, $pin, 'discounts.approve', 'discount-decision:'.$request->id);

            $request->forceFill([
                'status' => $approve ? ApprovalStatus::Approved : ApprovalStatus::Rejected,
                'decided_by' => $approver->id,
                'decided_at' => now(),
            ])->save();

            if ($approve) {
                /** @var Invoice $invoice */
                $invoice = Invoice::query()->whereKey($request->invoice_id)->lockForUpdate()->firstOrFail();
                $invoice->forceFill([
                    'discount_type' => $request->discount_type,
                    'discount_value' => $request->discount_value,
                    'discount_reason' => $request->reason,
                    'discount_approved_by' => $approver->id,
                ])->save();

                $invoice = $this->calculator->recalculate($invoice);
                $this->excess->open($invoice, $request->reason, $request->requested_by);
            }

            activity('billing')->causedBy($approver)->performedOn($request)->withProperties([
                'invoice_id' => $request->invoice_id,
                'approved' => $approve,
            ])->log($approve ? 'discount.approved' : 'discount.rejected');

            return $request->refresh();
        });
    }
}
