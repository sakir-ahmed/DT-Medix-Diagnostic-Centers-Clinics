<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveResultsRequest extends FormRequest
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
            'results' => ['nullable', 'array'],
            'results.*.value' => ['nullable', 'string', 'max:500'],
            'results.*.remarks' => ['nullable', 'string', 'max:500'],
            'findings' => ['nullable', 'string', 'max:20000'],
            'impression' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
