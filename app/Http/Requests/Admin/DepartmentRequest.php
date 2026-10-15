<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DepartmentCategory;
use App\Enums\ReportStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->where('tenant_id', tenant()?->id)->ignore($this->route('department'))],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'category' => ['required', Rule::enum(DepartmentCategory::class)],
            'report_style' => ['required', Rule::enum(ReportStyle::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
