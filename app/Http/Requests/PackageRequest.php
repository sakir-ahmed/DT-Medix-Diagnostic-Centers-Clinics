<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('packages', 'code')->where('tenant_id', $tenantId)->ignore($this->route('package'))],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999', ValidationRules::MONEY],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'test_ids' => ['required', 'array', 'min:2'],
            'test_ids.*' => ['integer', 'distinct', Rule::exists('tests', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
        ];
    }
}
