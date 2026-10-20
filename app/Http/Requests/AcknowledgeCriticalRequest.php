<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeCriticalRequest extends FormRequest
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
            'informed_name' => ['required', 'string', 'max:150'],
            'informed_at' => ['nullable', 'date'],
        ];
    }
}
