<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\ApprovalStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Recalculates every money column of an invoice from its items, discount,
 * payments and approved refunds. The due is always calculated.
 */
final class InvoiceCalculator
{
    public function recalculate(Invoice $invoice): Invoice
    {
        $tenant = tenant();
        /** @var \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items */
        $items = OrderItem::query()->where('order_id', $invoice->order_id)->orderBy('id')->get();
        $active = $items->filter(fn (OrderItem $item): bool => $item->status !== OrderItemStatus::Cancelled)->values();

        $subtotal = Money::add('0', ...$active->map(fn (OrderItem $i): string => (string) $i->price)->all());
        $discount = $this->discountAmount($invoice, $subtotal);
        $this->allocate($active, $subtotal, $discount);

        foreach ($items->filter(fn (OrderItem $i): bool => $i->status === OrderItemStatus::Cancelled) as $cancelled) {
            $cancelled->net_amount = '0.00';
        }

        $items->each(fn (OrderItem $item) => $item->isDirty() ? $item->save() : null);

        $net = Money::sub($subtotal, $discount);
        $vatBase = Money::add('0', ...$active->filter(fn (OrderItem $i): bool => $i->vat_applicable)->map(fn (OrderItem $i): string => (string) $i->net_amount)->all());
        $vat = $tenant?->setting('vat_enabled') ? Money::percent($vatBase, (string) $tenant->setting('vat_percent')) : '0.00';
        $service = $tenant?->setting('service_charge_enabled') ? Money::percent($net, (string) $tenant->setting('service_charge_percent')) : '0.00';
        $total = Money::add($net, $vat, $service);

        $paid = Money::add('0', ...$invoice->payments()->where('status', 'completed')->pluck('amount')->map(fn ($a): string => (string) $a)->all());
        $refunded = Money::add('0', ...$invoice->refunds()->where('status', ApprovalStatus::Approved->value)->pluck('amount')->map(fn ($a): string => (string) $a)->all());
        $netPaid = Money::sub($paid, $refunded);
        $due = Money::max('0', Money::sub($total, $netPaid));

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'vat_amount' => $vat,
            'service_charge' => $service,
            'total' => $total,
            'paid' => $paid,
            'refunded' => $refunded,
            'due' => $due,
        ]);

        $status = match (true) {
            $active->isEmpty() => Money::compare($refunded, '0') > 0 ? InvoiceStatus::Refunded : InvoiceStatus::Cancelled,
            Money::isZero($due) => InvoiceStatus::Paid,
            Money::compare($netPaid, '0') > 0 => InvoiceStatus::Partial,
            default => InvoiceStatus::Due,
        };

        if ($status === InvoiceStatus::Paid && $invoice->fully_paid_at === null) {
            $invoice->fully_paid_at = now();
        } elseif ($status !== InvoiceStatus::Paid) {
            $invoice->fully_paid_at = null;
        }

        $invoice->status = $status;
        $invoice->save();

        return $invoice;
    }

    /**
     * Money paid beyond the current total (to be refunded).
     */
    public static function overpaid(Invoice $invoice): string
    {
        return Money::max('0', Money::sub(Money::sub((string) $invoice->paid, (string) $invoice->refunded), (string) $invoice->total));
    }

    public function discountAmount(Invoice $invoice, string $subtotal): string
    {
        if ($invoice->discount_type === null || Money::isZero((string) $invoice->discount_value)) {
            return '0.00';
        }

        $amount = $invoice->discount_type === 'percent'
            ? Money::percent($subtotal, (string) $invoice->discount_value)
            : Money::of((string) $invoice->discount_value);

        return Money::min($amount, $subtotal);
    }

    /**
     * Spread the discount over items in proportion to price; the last item
     * takes the rounding remainder so the parts add up exactly.
     *
     * @param  Collection<int, OrderItem>  $items
     */
    private function allocate(Collection $items, string $subtotal, string $discount): void
    {
        $remaining = $discount;
        $last = $items->count() - 1;

        foreach ($items->values() as $index => $item) {
            $share = $index === $last || Money::isZero($subtotal)
                ? $remaining
                : Money::of(bcdiv(bcmul((string) $item->price, $discount, 6), $subtotal, 6));
            $share = Money::min($share, (string) $item->price);
            $remaining = Money::sub($remaining, $share);
            $item->net_amount = Money::sub((string) $item->price, $share);
        }
    }
}
