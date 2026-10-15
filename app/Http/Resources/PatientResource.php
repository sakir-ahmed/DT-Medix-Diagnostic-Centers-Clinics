<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_code' => $this->patient_code,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'sex' => $this->sex->value,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'dob_estimated' => $this->dob_estimated,
            'age_text' => $this->ageText(),
            'age_years' => $this->ageYears(),
            'mobile' => $this->mobileDisplay(),
            'relation_to_mobile_owner' => $this->relation_to_mobile_owner,
            'address' => $this->address,
            'blood_group' => $this->blood_group,
            'nid_last4' => $this->nid === null ? null : substr($this->nid, -4),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_mobile' => $this->emergency_mobile === null ? null : substr($this->emergency_mobile, 2),
            'allergies' => $this->allergies,
            'notes' => $this->notes,
            'preferred_locale' => $this->preferred_locale,
            'messages_opt_out' => $this->messages_opt_out,
            'has_photo' => $this->photo_path !== null,
            'registered_branch_id' => $this->registered_branch_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
