<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MessageTemplateRequest extends FormRequest
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
            'body_en' => ['required', 'string', 'max:480'],
            'body_bn' => ['required', 'string', 'max:335'],
            'whatsapp_template' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'is_active' => ['boolean'],
        ];
    }
}
