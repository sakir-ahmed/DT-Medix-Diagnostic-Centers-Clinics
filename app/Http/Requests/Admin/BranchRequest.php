<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
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
        $branch = $this->route('branch');

        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('branches', 'code')
                ->where('tenant_id', tenant()?->id)
                ->ignore($branch)],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', ValidationRules::MOBILE],
            'email' => ['nullable', 'email', 'max:150'],
            'license_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
