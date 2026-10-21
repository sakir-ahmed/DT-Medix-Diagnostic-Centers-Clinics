<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicBookingRequest extends FormRequest
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
            'doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:'.now()->addDays(15)->toDateString()],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', ValidationRules::MOBILE],
            'sex' => ['required', Rule::enum(Sex::class)],
            'age_years' => ['required', 'integer', 'min:0', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'doctor_id' => __('public.field_doctor'),
            'date' => __('public.field_date'),
            'time' => __('public.field_time'),
            'name' => __('public.field_patient_name'),
            'mobile' => __('public.field_mobile'),
            'sex' => __('public.field_sex'),
            'age_years' => __('public.field_age'),
        ];
    }
}
