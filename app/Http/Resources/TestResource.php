<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReferenceRange;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Test
 */
class TestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department->name),
            'report_template_id' => $this->report_template_id,
            'sample_type' => $this->sample_type,
            'container' => $this->container,
            'price' => money($this->price),
            'tat_hours' => $this->tat_hours,
            'preparation' => $this->preparation,
            'method' => $this->method,
            'interpretation' => $this->interpretation,
            'home_collection' => $this->home_collection,
            'is_outsourced' => $this->is_outsourced,
            'vendor_name' => $this->vendor_name,
            'vendor_cost' => $this->vendor_cost === null ? null : money($this->vendor_cost),
            'vat_applicable' => $this->vat_applicable,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'parameters' => $this->whenLoaded('parameters', fn () => $this->parameters->map(fn (TestParameter $p): array => self::parameter($p))->values()->all()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function parameter(TestParameter $parameter): array
    {
        return [
            'id' => $parameter->id,
            'code' => $parameter->code,
            'name' => $parameter->name,
            'group_label' => $parameter->group_label,
            'unit' => $parameter->unit,
            'result_type' => $parameter->result_type->value,
            'choices' => $parameter->choices ?? [],
            'abnormal_values' => $parameter->abnormal_values ?? [],
            'formula' => $parameter->formula,
            'decimals' => $parameter->decimals,
            'default_value' => $parameter->default_value,
            'is_active' => $parameter->is_active,
            'ranges' => $parameter->ranges->map(fn (ReferenceRange $r): array => [
                'id' => $r->id,
                'sex' => $r->sex,
                'age_min' => $r->age_min,
                'age_max' => $r->age_max,
                'age_unit' => $r->age_unit,
                'low' => $r->low === null ? null : ReferenceRange::trim($r->low),
                'high' => $r->high === null ? null : ReferenceRange::trim($r->high),
                'critical_low' => $r->critical_low === null ? null : ReferenceRange::trim($r->critical_low),
                'critical_high' => $r->critical_high === null ? null : ReferenceRange::trim($r->critical_high),
                'display_text' => $r->display_text,
                'text' => $r->text(),
            ])->values()->all(),
        ];
    }
}
