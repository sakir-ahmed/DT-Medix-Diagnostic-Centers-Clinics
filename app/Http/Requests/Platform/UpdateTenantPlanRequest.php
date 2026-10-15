<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\Module;
use App\Enums\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantPlanRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'plan' => ['required', Rule::enum(Plan::class)],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'limits.branches' => ['nullable', 'integer', 'min:1'],
            'limits.users' => ['nullable', 'integer', 'min:1'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(Module::values())],
            'message_credits' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
