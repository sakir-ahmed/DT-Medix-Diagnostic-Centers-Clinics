<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Restricts queries to the branches the current user may access. Owners and
 * users with "branches.all" have no restriction (branchIds() returns null).
 *
 * @mixin Model
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $builder): void {
            $branchIds = app(BranchContext::class)->branchIds();

            if ($branchIds !== null) {
                $builder->whereIn($builder->qualifyColumn('branch_id'), $branchIds);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('branch_id') === null) {
                $model->setAttribute('branch_id', app(BranchContext::class)->currentBranchId());
            }
        });
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
