<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'doctor_id' => $this->doctor_id,
            'patient_id' => $this->patient_id,
            'date' => $this->appointment_date->toDateString(),
            'time' => substr((string) $this->start_time, 0, 5),
            'token_no' => $this->token_no,
            'visit_type' => $this->visit_type,
            'status' => $this->status->value,
            'source' => $this->source,
            'fee' => money($this->fee),
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'waiting_minutes' => $this->arrived_at === null || $this->status->value === 'completed' ? null : (int) $this->arrived_at->diffInMinutes(now()),
            'allowed' => array_map(fn ($status) => $status->value, $this->status->next()),
            'doctor' => $this->whenLoaded('doctor', fn (): array => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'patient' => $this->whenLoaded('patient', fn (): array => [
                'id' => $this->patient->id,
                'patient_code' => $this->patient->patient_code,
                'name' => $this->patient->name,
                'age_text' => $this->patient->ageText(),
                'sex' => $this->patient->sex->value,
            ]),
        ];
    }
}
