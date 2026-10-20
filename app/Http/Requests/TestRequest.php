<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ResultType;
use App\Models\Branch;
use App\Models\ReferenceRange;
use App\Models\Test;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Test master with parameters and reference ranges (admin and API).
 */
class TestRequest extends FormRequest
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
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'report_template_id' => ['nullable', 'integer', Rule::exists('report_templates', 'id')],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('tests', 'code')->ignore($this->route('test'))],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'sample_type' => ['nullable', Rule::in(Test::SAMPLE_TYPES)],
            'container' => ['nullable', Rule::in(Test::CONTAINERS)],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999', ValidationRules::MONEY],
            'tat_hours' => ['required', 'integer', 'min:1', 'max:2160'],
            'preparation' => ['nullable', 'string', 'max:500'],
            'method' => ['nullable', 'string', 'max:500'],
            'interpretation' => ['nullable', 'string', 'max:2000'],
            'home_collection' => ['boolean'],
            'is_outsourced' => ['boolean'],
            'vendor_name' => ['nullable', 'required_if:is_outsourced,true', 'string', 'max:150'],
            'vendor_cost' => ['nullable', 'numeric', 'min:0', ValidationRules::MONEY],
            'vat_applicable' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'parameters' => ['sometimes', 'array', 'max:80'],
            'parameters.*.id' => ['nullable', 'integer'],
            'parameters.*.code' => ['required', 'string', 'max:30', 'alpha_dash', 'distinct:ignore_case'],
            'parameters.*.name' => ['required', 'string', 'max:150'],
            'parameters.*.group_label' => ['nullable', 'string', 'max:100'],
            'parameters.*.unit' => ['nullable', 'string', 'max:30'],
            'parameters.*.result_type' => ['required', Rule::enum(ResultType::class)],
            'parameters.*.choices' => ['nullable', 'array', 'required_if:parameters.*.result_type,choice'],
            'parameters.*.choices.*' => ['string', 'max:60'],
            'parameters.*.abnormal_values' => ['nullable', 'array'],
            'parameters.*.abnormal_values.*' => ['string', 'max:60'],
            'parameters.*.formula' => ['nullable', 'required_if:parameters.*.result_type,calculated', 'string', 'max:255'],
            'parameters.*.decimals' => ['nullable', 'integer', 'min:0', 'max:4'],
            'parameters.*.default_value' => ['nullable', 'string', 'max:60'],
            'parameters.*.is_active' => ['boolean'],
            'parameters.*.ranges' => ['nullable', 'array', 'max:20'],
            'parameters.*.ranges.*.sex' => ['required', Rule::in(['any', 'male', 'female'])],
            'parameters.*.ranges.*.age_min' => ['required', 'integer', 'min:0', 'max:1500'],
            'parameters.*.ranges.*.age_max' => ['nullable', 'integer', 'min:1', 'max:1500', 'gt:parameters.*.ranges.*.age_min'],
            'parameters.*.ranges.*.age_unit' => ['required', Rule::in(ReferenceRange::AGE_UNITS)],
            'parameters.*.ranges.*.low' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.high' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.critical_low' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.critical_high' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.display_text' => ['nullable', 'string', 'max:100'],

            'branch_prices' => ['sometimes', 'array'],
            'branch_prices.*' => ['nullable', 'numeric', 'min:0', ValidationRules::MONEY],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $branchIds = array_keys((array) $this->input('branch_prices', []));

            if ($branchIds !== [] && Branch::query()->whereIn('id', $branchIds)->count() !== count($branchIds)) {
                $validator->errors()->add('branch_prices', __('validation.exists', ['attribute' => __('fields.branch')]));
            }
        }];
    }
}
