<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sample
 */
class SampleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sample_code' => $this->sample_code,
            'status' => $this->status->value,
            'sample_type' => $this->sample_type,
            'container' => $this->container,
            'branch_id' => $this->branch_id,
            'order_id' => $this->order_id,
            'urgency' => $this->whenLoaded('order', fn (): string => ($this->order->urgency ?? 'routine') === 'urgent' ? 'urgent' : 'routine'),
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'patient_code' => $this->patient->patient_code,
                'name' => $this->patient->name,
                'name_bn' => $this->patient->name_bn,
                'age_text' => $this->patient->ageText(),
                'sex' => $this->patient->sex->value,
            ]),
            'tests' => $this->whenLoaded('items', fn (): array => $this->items
                ->filter(fn ($item): bool => $item->status->value !== 'cancelled')
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'department_id' => $item->department_id,
                    'status' => $item->status->value,
                    'expected_at' => $item->expected_at?->toIso8601String(),
                ])->values()->all()),
            'collected_at' => $this->collected_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'reject_reason' => $this->reject_reason,
            'recollection_of_id' => $this->recollection_of_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
