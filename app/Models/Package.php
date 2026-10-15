<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A group of tests sold at a package price (for example "Diabetes Panel").
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $name_bn
 * @property string $price
 * @property string|null $description
 * @property bool $is_active
 * @property-read Collection<int, Test> $tests
 */
class Package extends Model
{
    use BelongsToTenant;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'name_bn', 'price', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'name', 'price', 'is_active'])->logOnlyDirty();
    }

    /**
     * @return BelongsToMany<Test, $this>
     */
    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class)->orderBy('tests.sort_order')->orderBy('tests.name');
    }
}
