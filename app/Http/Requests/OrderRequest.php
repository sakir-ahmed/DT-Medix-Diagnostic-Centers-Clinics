<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new test order from the counter or the API.
 */
class OrderRequest extends FormRequest
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
        $tenantId = tenant()?->id;

        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('tenant_id', $tenantId)],
            'test_ids' => ['array'],
            'test_ids.*' => ['integer', Rule::exists('tests', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'package_ids' => ['array'],
            'package_ids.*' => ['integer', Rule::exists('packages', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'referrer_id' => ['nullable', 'integer', Rule::exists('referrers', 'id')->where('tenant_id', $tenantId)],
            'urgency' => ['nullable', Rule::in(['routine', 'urgent'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'discount_category' => ['nullable', Rule::in(['standard', 'referrer', 'free'])],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'insist_discount' => ['boolean'],
            'approval_login' => ['nullable', 'string', 'max:150'],
            'approval_pin' => ['nullable', 'string', 'max:20'],
            'payments' => ['array'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999', ValidationRules::MONEY],
            'payments.*.method' => ['nullable', Rule::in(['cash'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tests = $this->input('test_ids', []);
            $packages = $this->input('package_ids', []);

            if ($tests === [] && $packages === []) {
                $validator->errors()->add('test_ids', __('validation.required'));
            }
        });
    }
}
