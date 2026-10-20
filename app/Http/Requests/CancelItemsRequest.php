<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelItemsRequest extends FormRequest
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
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
            'reason' => ['required', 'string', 'max:255'],
            'approval_login' => ['nullable', 'string', 'max:150'],
            'approval_pin' => ['nullable', 'string', 'max:20'],
        ];
    }
}
