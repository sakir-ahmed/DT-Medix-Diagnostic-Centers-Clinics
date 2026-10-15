<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A test in the catalog (CBC, Lipid profile, X-ray chest...).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $department_id
 * @property int|null $report_template_id
 * @property string $code
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $sample_type
 * @property string|null $container
 * @property string $price
 * @property int $tat_hours
 * @property string|null $preparation
 * @property string|null $method
 * @property string|null $interpretation
 * @property bool $home_collection
 * @property bool $is_outsourced
 * @property string|null $vendor_name
 * @property string|null $vendor_cost
 * @property bool $vat_applicable
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Department $department
 */
class Test extends Model
{
    use BelongsToTenant;
    use LogsActivity;
    use SoftDeletes;

    public const SAMPLE_TYPES = ['blood', 'serum', 'plasma', 'urine', 'stool', 'swab', 'sputum', 'csf', 'fluid', 'tissue', 'none'];

    public const CONTAINERS = ['edta', 'plain', 'gel', 'fluoride', 'citrate', 'heparin', 'urine_cup', 'stool_cup', 'swab_tube', 'none'];

    protected $fillable = [
        'department_id', 'report_template_id', 'code', 'name', 'name_bn', 'sample_type', 'container', 'price', 'tat_hours',
        'preparation', 'method', 'interpretation', 'home_collection', 'is_outsourced', 'vendor_name', 'vendor_cost',
        'vat_applicable', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'vendor_cost' => 'decimal:2',
            'tat_hours' => 'integer',
            'home_collection' => 'boolean',
            'is_outsourced' => 'boolean',
            'vat_applicable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'name', 'price', 'tat_hours', 'is_active', 'is_outsourced'])->logOnlyDirty();
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<ReportTemplate, $this>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    /**
     * @return HasMany<TestParameter, $this>
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(TestParameter::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    /**
     * @return HasMany<BranchTestPrice, $this>
     */
    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchTestPrice::class);
    }
}
