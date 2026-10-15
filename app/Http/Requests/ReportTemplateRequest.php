<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReportStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(ReportStyle::class)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('tenant_id', tenant()?->id)],
            'options' => ['nullable', 'array'],
            'options.*' => ['boolean'],
            'default_findings' => ['nullable', 'string', 'max:10000'],
            'default_impression' => ['nullable', 'string', 'max:5000'],
            'footer_note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'phrases' => ['sometimes', 'array', 'max:200'],
            'phrases.*.shortcut' => ['required', 'string', 'max:40', 'alpha_dash', 'distinct'],
            'phrases.*.section' => ['required', Rule::in(['findings', 'impression'])],
            'phrases.*.text' => ['required', 'string', 'max:2000'],
        ];
    }
}
