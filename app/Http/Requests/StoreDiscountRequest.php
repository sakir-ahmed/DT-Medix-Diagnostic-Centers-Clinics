<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends FormRequest
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
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'discount_category' => ['nullable', Rule::in(['standard', 'referrer', 'free'])],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'insist_discount' => ['boolean'],
            'approval_login' => ['nullable', 'string', 'max:150'],
            'approval_pin' => ['nullable', 'string', 'max:20'],
        ];
    }
}
