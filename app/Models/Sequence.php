<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant gapless counters (patient codes, invoice numbers, sample IDs).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property int $value
 */
class Sequence extends Model
{
    use BelongsToTenant;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }
}
