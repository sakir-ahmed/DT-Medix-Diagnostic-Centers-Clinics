<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualAttendanceRequest extends FormRequest
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
            'employee_id' => ['required', 'integer', Rule::exists(Employee::class, 'id')],
            'work_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
