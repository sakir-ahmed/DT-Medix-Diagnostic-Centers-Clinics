<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\WorkShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property int $grace_minutes
 * @property list<int>|null $weekly_off
 * @property bool $is_active
 */
class WorkShift extends Model
{
    /** @use HasFactory<WorkShiftFactory> */
    use BelongsToBranch;

    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'start_time', 'end_time', 'grace_minutes', 'weekly_off', 'is_active'];

    protected function casts(): array
    {
        return [
            'weekly_off' => 'array',
            'grace_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isWeeklyOff(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->weekly_off ?? [], true);
    }
}
