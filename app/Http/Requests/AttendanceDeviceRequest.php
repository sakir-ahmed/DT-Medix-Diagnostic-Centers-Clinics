<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceDeviceRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:60', Rule::unique('attendance_devices', 'serial_number')->ignore($this->route('device'))],
            'location' => ['nullable', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }
}
