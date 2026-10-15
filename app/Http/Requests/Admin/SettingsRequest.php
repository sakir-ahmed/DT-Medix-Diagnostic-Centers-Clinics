<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'contact_mobile' => ['nullable', ValidationRules::MOBILE],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'settings.release_rule' => ['required', Rule::in(['hold_until_paid', 'manager_override', 'always'])],
            'settings.discount_limit_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.vat_enabled' => ['boolean'],
            'settings.vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.service_charge_enabled' => ['boolean'],
            'settings.service_charge_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.allow_self_verification' => ['boolean'],
            'settings.report_link_days' => ['required', 'integer', 'min:1', 'max:365'],
            'settings.follow_up_days' => ['required', 'integer', 'min:0', 'max:90'],
            'settings.follow_up_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.delta_check_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'settings.commission_basis' => ['required', Rule::in(['paid', 'billed'])],
            'settings.commission_default_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.commission_enabled' => ['boolean'],
            'settings.default_locale' => ['required', Rule::in(['en', 'bn'])],
            'settings.idle_timeout_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'settings.tat_target_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'settings.collected_samples_refundable' => ['boolean'],
        ];
    }
}
