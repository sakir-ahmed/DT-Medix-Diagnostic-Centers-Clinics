<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gapless counters (patient codes, invoice numbers, sample IDs).
 *
 * @property int $id
 * @property string $key
 * @property int $value
 */
class Sequence extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }
}
