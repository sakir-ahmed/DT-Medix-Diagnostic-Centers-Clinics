<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'specialty' => $this->specialty,
            'qualifications' => $this->qualifications,
            'designation' => $this->designation,
            'bmdc_no' => $this->bmdc_no,
            'mobile' => $this->mobile === null ? null : substr($this->mobile, 2),
            'email' => $this->email,
            'consultation_fee' => money($this->consultation_fee),
            'follow_up_fee' => money($this->follow_up_fee),
            'follow_up_days' => $this->follow_up_days,
            'slot_minutes' => $this->slot_minutes,
            'is_visiting' => $this->is_visiting,
            'is_active' => $this->is_active,
            'user_id' => $this->user_id,
            'has_signature' => $this->signature_path !== null,
        ];
    }
}
