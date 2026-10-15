<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
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
            'name_bn' => ['nullable', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('tenants', 'slug')],
            'domain' => ['nullable', 'string', 'max:150', Rule::unique('tenants', 'domain')],
            'plan' => ['required', Rule::enum(Plan::class)],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:today'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_mobile' => ['nullable', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'message_credits' => ['nullable', 'integer', 'min:0'],
            'owner.name' => ['required', 'string', 'max:150'],
            'owner.email' => ['required_without:owner.mobile', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')],
            'owner.mobile' => ['required_without:owner.email', 'nullable', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'owner.username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'owner.password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'owner.name' => __('platform.owner_name'),
            'owner.email' => __('fields.email'),
            'owner.mobile' => __('fields.mobile'),
            'owner.password' => __('fields.password'),
        ];
    }
}
