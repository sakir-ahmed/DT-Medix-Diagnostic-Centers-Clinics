<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One raw fingerprint, card or face punch.
 *
 * @property int $id
 * @property int $branch_id
 * @property int|null $employee_id
 * @property int|null $attendance_device_id
 * @property string $device_pin
 * @property Carbon $punched_at
 * @property string $source
 */
class AttendancePunch extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'employee_id', 'attendance_device_id', 'device_pin', 'punched_at', 'source'];

    protected function casts(): array
    {
        return ['punched_at' => 'datetime'];
    }
}
