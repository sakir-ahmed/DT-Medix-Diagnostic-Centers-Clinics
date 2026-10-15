<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\Patient;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by the admin and the API (create and update).
 */
class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->route('patient') === null;

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:2', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'sex' => [$creating ? 'required' : 'sometimes', Rule::enum(Sex::class)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'age_years' => ['nullable', 'integer', 'min:0', 'max:130', Rule::requiredIf($creating && ! $this->filled('date_of_birth') && ! $this->filled('age_months'))],
            'age_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'mobile' => ['nullable', ValidationRules::MOBILE],
            'relation_to_mobile_owner' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_group' => ['nullable', Rule::in(Patient::BLOOD_GROUPS)],
            'nid' => ['nullable', 'string', 'regex:/^\d{10}(\d{3}|\d{7})?$/'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_mobile' => ['nullable', ValidationRules::MOBILE],
            'allergies' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'preferred_locale' => ['nullable', Rule::in(['en', 'bn'])],
            'messages_opt_out' => ['boolean'],
            'confirm_new' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nid.regex' => __('patients.nid_format'),
            'age_years.required' => __('patients.age_or_dob_required'),
        ];
    }
}
