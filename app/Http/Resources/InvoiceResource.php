<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'patient_id' => $this->patient_id,
            'invoice_no' => $this->invoice_no,
            'subtotal' => money($this->subtotal),
            'discount_type' => $this->discount_type,
            'discount_value' => money($this->discount_value),
            'discount_amount' => money($this->discount_amount),
            'discount_reason' => $this->discount_reason,
            'vat_amount' => money($this->vat_amount),
            'service_charge' => money($this->service_charge),
            'total' => money($this->total),
            'paid' => money($this->paid),
            'refunded' => money($this->refunded),
            'due' => money($this->due),
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'receipt_no' => $payment->receipt_no,
                'method' => $payment->method->value,
                'amount' => money($payment->amount),
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ])->values()->all()),
        ];
    }
}
