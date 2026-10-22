<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkShiftRequest extends FormRequest
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
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'weekly_off' => ['nullable', 'array'],
            'weekly_off.*' => ['integer', 'between:0,6'],
            'is_active' => ['boolean'],
        ];
    }
}
