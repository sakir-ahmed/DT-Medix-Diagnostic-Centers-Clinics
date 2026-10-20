<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'patient_id' => $this->patient_id,
            'appointment_id' => $this->appointment_id,
            'referrer_id' => $this->referrer_id,
            'urgency' => $this->urgency,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'patient_code' => $this->patient->patient_code,
                'name' => $this->patient->name,
                'name_bn' => $this->patient->name_bn,
                'sex' => $this->patient->sex->value,
                'age_text' => $this->patient->ageText(),
                'mobile' => $this->patient->mobileDisplay(),
                'preferred_locale' => $this->patient->preferred_locale,
                'allergies' => $this->patient->allergies,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null ? null : (new InvoiceResource($this->invoice))->resolve()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'test_id' => $item->test_id,
                'package_id' => $item->package_id,
                'sample_id' => $item->sample_id,
                'code' => $item->code,
                'name' => $item->name,
                'price' => money($item->price),
                'net_amount' => money($item->net_amount),
                'tat_hours' => $item->tat_hours,
                'expected_at' => $item->expected_at?->toIso8601String(),
                'status' => $item->status->value,
                'cancel_reason' => $item->cancel_reason,
            ])->values()->all()),
            'samples' => $this->whenLoaded('samples', fn () => $this->samples->map(fn (Sample $sample): array => [
                'id' => $sample->id,
                'sample_code' => $sample->sample_code,
                'sample_type' => $sample->sample_type,
                'container' => $sample->container,
                'status' => $sample->status->value,
            ])->values()->all()),
        ];
    }
}
