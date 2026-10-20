<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
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
        $tenantId = tenant()?->id;

        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('tenant_id', $tenantId)],
            'doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('tenant_id', $tenantId)],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'source' => ['nullable', Rule::in(['walk-in', 'phone'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
