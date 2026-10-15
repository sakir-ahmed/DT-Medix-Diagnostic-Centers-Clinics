<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DepartmentCategory;
use App\Enums\ReportStyle;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $name_bn
 * @property DepartmentCategory $category
 * @property ReportStyle $report_style
 * @property int $sort_order
 * @property bool $is_active
 */
class Department extends Model
{
    use BelongsToTenant;

    protected $fillable = ['code', 'name', 'name_bn', 'category', 'report_style', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'category' => DepartmentCategory::class,
            'report_style' => ReportStyle::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Test, $this>
     */
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }
}
