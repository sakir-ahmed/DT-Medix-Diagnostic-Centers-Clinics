<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\OrderItemStatus;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ApprovalService;
use App\Services\Billing\ExcessRefund;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Cancels order lines. Lines not yet collected cancel with a reason.
 * Collected lines cancel only when the tenant allows a refund and a
 * manager approves with login and PIN.
 */
final class CancelItemsAction
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly InvoiceCalculator $calculator,
        private readonly ExcessRefund $excess,
    ) {}

    /**
     * @param  list<int>  $itemIds
     */
    public function execute(Order $order, array $itemIds, string $reason, ?string $login = null, ?string $pin = null, ?int $requestedBy = null): Order
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw DomainException::make('REASON_REQUIRED', 422);
        }

        return DB::transaction(function () use ($order, $itemIds, $reason, $login, $pin, $requestedBy): Order {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $itemIds = array_values(array_unique($itemIds));
            $items = OrderItem::query()->where('order_id', $order->id)->whereIn('id', $itemIds)->get();

            if ($items->count() !== count($itemIds)) {
                throw DomainException::make('NOT_FOUND', 404);
            }

            $collected = $items->contains(fn (OrderItem $item): bool => $item->status->isCollected());

            if ($collected) {
                if (! (bool) tenant()?->setting('collected_samples_refundable')) {
                    throw DomainException::make('COLLECTED_NOT_REFUNDABLE', 409);
                }

                $this->approvals->verify($login, $pin, 'cancellations.approve', 'cancel:'.$order->id);
            }

            foreach ($items as $item) {
                if ($item->status === OrderItemStatus::Cancelled) {
                    continue;
                }

                $item->forceFill([
                    'status' => OrderItemStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ])->save();
            }

            $remaining = OrderItem::query()->where('order_id', $order->id)->where('status', '!=', OrderItemStatus::Cancelled->value)->exists();
            $order->forceFill(['status' => $remaining ? 'active' : 'cancelled'])->save();

            $invoice = $order->invoice;

            if ($invoice !== null) {
                $invoice = $this->calculator->recalculate($invoice);
                $this->excess->open($invoice, $reason, $requestedBy);
            }

            activity('billing')->performedOn($order)->withProperties([
                'order_id' => $order->id,
                'item_ids' => $itemIds,
            ])->log('order.items_cancelled');

            return $order->load('invoice', 'items');
        });
    }
}
