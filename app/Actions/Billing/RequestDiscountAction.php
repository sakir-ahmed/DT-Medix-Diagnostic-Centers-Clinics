<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\ApprovalStatus;
use App\Exceptions\DomainException;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\Billing\DiscountGuard;
use App\Services\Billing\ExcessRefund;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Asks for a discount on an existing invoice, or applies it when the
 * caller is allowed to approve it.
 */
final class RequestDiscountAction
{
    public function __construct(
        private readonly DiscountGuard $discounts,
        private readonly ApprovalService $approvals,
        private readonly InvoiceCalculator $calculator,
        private readonly ExcessRefund $excess,
    ) {}

    public function execute(Invoice $invoice, ?string $type, ?string $value, string $category, ?string $reason, ?User $actor = null, bool $insist = false, ?string $login = null, ?string $pin = null): DiscountRequest
    {
        $reason = trim((string) $reason);

        return DB::transaction(function () use ($invoice, $type, $value, $category, $reason, $actor, $insist, $login, $pin): DiscountRequest {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->discountRequests()->where('status', ApprovalStatus::Pending)->exists()) {
                throw DomainException::make('INVALID_STATE', 409);
            }

            $assessed = $this->discounts->assess($type, $value, $category, (string) $invoice->subtotal);

            if ($assessed === null) {
                throw DomainException::make('VALIDATION_FAILED', 422);
            }

            if ((in_array($category, ['referrer', 'free'], true) || $assessed['exceeds']) && $reason === '') {
                throw DomainException::make('REASON_REQUIRED', 422);
            }

            $approvedBy = null;

            if ($assessed['exceeds']) {
                if ($actor?->can('discounts.approve')) {
                    $approvedBy = $actor->id;
                } elseif ($login !== null && $login !== '') {
                    $approvedBy = $this->approvals->verify($login, $pin, 'discounts.approve', 'discount-request:'.$invoice->id)->id;
                } elseif ($insist) {
                    throw DomainException::make('DISCOUNT_LIMIT_EXCEEDED', 422);
                }
            }

            $request = DiscountRequest::query()->create([
                'branch_id' => $invoice->branch_id,
                'invoice_id' => $invoice->id,
                'discount_type' => $assessed['type'],
                'discount_value' => $assessed['value'],
                'reason' => $reason !== '' ? $reason : '-',
                'status' => $approvedBy === null && $assessed['exceeds'] ? ApprovalStatus::Pending : ApprovalStatus::Approved,
                'requested_by' => $actor?->id,
            ]);

            if ($request->status === ApprovalStatus::Approved) {
                $request->forceFill(['decided_by' => $approvedBy ?? $actor?->id, 'decided_at' => now()])->save();
                $invoice->forceFill([
                    'discount_type' => $assessed['type'],
                    'discount_value' => $assessed['value'],
                    'discount_reason' => $reason !== '' ? $reason : null,
                    'discount_approved_by' => $approvedBy ?? $actor?->id,
                ])->save();
                $invoice = $this->calculator->recalculate($invoice);
                $this->excess->open($invoice, $reason !== '' ? $reason : __('billing.discount_applied'), $actor?->id);
            }

            activity('billing')->performedOn($request)->withProperties([
                'invoice_id' => $invoice->id,
                'discount_type' => $assessed['type'],
                'discount_value' => $assessed['value'],
            ])->log('discount.requested');

            return $request->refresh();
        });
    }
}
