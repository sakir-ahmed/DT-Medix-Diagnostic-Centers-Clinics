<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Package;
use App\Models\Test;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Package
 */
class PackageResource extends JsonResource
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
            'price' => money($this->price),
            'description' => $this->description,
            'is_active' => $this->is_active,
            'test_ids' => $this->whenLoaded('tests', fn () => $this->tests->pluck('id')->all()),
            'tests' => $this->whenLoaded('tests', fn () => $this->tests->map(fn (Test $t): array => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name, 'price' => money($t->price)])->all()),
            'tests_total' => $this->whenLoaded('tests', fn () => Money::add('0', ...$this->tests->map(fn (Test $t): string => (string) $t->price)->all())),
        ];
    }
}
