<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
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
            'invoice_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999', ValidationRules::MONEY],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
