<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\BranchContext;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
        $employee = $this->route('employee');
        $branchId = $employee?->branch_id ?? app(BranchContext::class)->currentBranchId();

        return [
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', ValidationRules::MOBILE],
            'joined_on' => ['required', 'date'],
            'device_pin' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'device_pin')->where('branch_id', $branchId)->ignore($employee)],
            'work_shift_id' => ['nullable', 'integer', Rule::exists('work_shifts', 'id')->where('branch_id', $branchId)],
            'basic_salary' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'house_allowance' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'medical_allowance' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'transport_allowance' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'other_allowance' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'overtime_rate' => ['nullable', ValidationRules::MONEY, 'min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'payment_account' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
