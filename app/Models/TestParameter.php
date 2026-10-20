<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResultType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $test_id
 * @property string $code
 * @property string $name
 * @property string|null $group_label
 * @property string|null $unit
 * @property ResultType $result_type
 * @property list<string>|null $choices
 * @property list<string>|null $abnormal_values
 * @property string|null $formula
 * @property int|null $decimals
 * @property string|null $default_value
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Collection<int, ReferenceRange> $ranges
 */
class TestParameter extends Model
{
    protected $fillable = ['code', 'name', 'group_label', 'unit', 'result_type', 'choices', 'abnormal_values', 'formula', 'decimals', 'default_value', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'result_type' => ResultType::class,
            'choices' => 'array',
            'abnormal_values' => 'array',
            'decimals' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * @return HasMany<ReferenceRange, $this>
     */
    public function ranges(): HasMany
    {
        return $this->hasMany(ReferenceRange::class);
    }
}
