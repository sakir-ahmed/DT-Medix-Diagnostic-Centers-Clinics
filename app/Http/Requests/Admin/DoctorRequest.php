<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorRequest extends FormRequest
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
        $doctor = $this->route('doctor');

        return [
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:150'],
            'bmdc_no' => ['nullable', 'string', 'max:30', Rule::unique('doctors', 'bmdc_no')->where('tenant_id', tenant()?->id)->ignore($doctor)],
            'mobile' => ['nullable', ValidationRules::MOBILE],
            'email' => ['nullable', 'email', 'max:150'],
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:9999999', ValidationRules::MONEY],
            'follow_up_fee' => ['required', 'numeric', 'min:0', 'max:9999999', ValidationRules::MONEY],
            'follow_up_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'slot_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'is_visiting' => ['boolean'],
            'is_active' => ['boolean'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', tenant()?->id)],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ];
    }
}
