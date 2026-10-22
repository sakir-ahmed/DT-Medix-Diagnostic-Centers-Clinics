<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Database\Factories\AttendanceDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A ZKTeco (ADMS push) attendance device registered by serial number.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $serial_number
 * @property string|null $location
 * @property Carbon|null $last_seen_at
 * @property bool $is_active
 */
class AttendanceDevice extends Model
{
    use BelongsToBranch;

    /** @use HasFactory<AttendanceDeviceFactory> */
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'serial_number', 'location', 'last_seen_at', 'is_active'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
