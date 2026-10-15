<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile_normalized' => normalize_bd_mobile($this->string('mobile')->toString())]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $staff = $this->route('staff');
        $roles = array_map(fn (RoleName $role): string => $role->value, RoleName::tenantRoles());

        return [
            'name' => ['required', 'string', 'max:150'],
            'designation' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'required_without_all:email,mobile', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($staff)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($staff)],
            'mobile' => ['nullable', ValidationRules::MOBILE],
            'mobile_normalized' => ['nullable', Rule::unique('users', 'mobile')->ignore($staff)],
            'password' => [$staff === null ? 'required' : 'nullable', 'string', Password::defaults()],
            'role' => ['required', Rule::in($roles)],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')->where('tenant_id', tenant()?->id)],
            'is_active' => ['boolean'],
            'locale' => ['nullable', Rule::in(['en', 'bn'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['mobile_normalized.unique' => __('validation.unique', ['attribute' => __('fields.mobile')])];
    }
}
