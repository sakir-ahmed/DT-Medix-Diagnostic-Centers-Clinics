<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ReferrerType;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReferrerRequest extends FormRequest
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
        $referrer = $this->route('referrer');

        return [
            'type' => ['required', Rule::enum(ReferrerType::class)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('referrers', 'code')->ignore($referrer)],
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'mobile' => ['nullable', ValidationRules::MOBILE],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'payment_account' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
