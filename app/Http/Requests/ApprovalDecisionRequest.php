<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalDecisionRequest extends FormRequest
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
            'approve' => ['required', 'boolean'],
            'approval_login' => ['required', 'string', 'max:150'],
            'approval_pin' => ['required', 'string', 'max:20'],
        ];
    }
}
