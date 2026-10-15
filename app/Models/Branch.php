<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $license_no
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 */
class Branch extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'name_bn', 'address', 'phone', 'email', 'license_no', 'is_active', 'settings'];

    protected static function booted(): void
    {
        // A branch is itself branch-scoped: non-owner users only see their branches.
        static::addGlobalScope('accessible', function (Builder $builder): void {
            $branchIds = app(TenantContext::class)->branchIds();

            if ($branchIds !== null) {
                $builder->whereIn($builder->qualifyColumn('id'), $branchIds);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'name', 'is_active'])->logOnlyDirty();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
