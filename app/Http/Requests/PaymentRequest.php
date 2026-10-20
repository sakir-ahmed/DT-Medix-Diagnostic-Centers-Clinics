<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
            'lines' => ['required', 'array', 'min:1', 'max:10'],
            'lines.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999', ValidationRules::MONEY],
            'lines.*.method' => ['nullable', Rule::in(['cash'])],
        ];
    }
}
