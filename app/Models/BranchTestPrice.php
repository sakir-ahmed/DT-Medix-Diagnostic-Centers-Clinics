<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $branch_id
 * @property int $test_id
 * @property string $price
 */
class BranchTestPrice extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    protected $fillable = ['branch_id', 'test_id', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
