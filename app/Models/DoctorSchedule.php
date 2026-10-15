<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Weekly schedule of a doctor at one branch (weekday 0 = Sunday).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $doctor_id
 * @property int $branch_id
 * @property int $weekday
 * @property string $start_time
 * @property string $end_time
 * @property int|null $slot_minutes
 * @property int|null $max_patients
 * @property bool $is_active
 */
class DoctorSchedule extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    protected $fillable = ['doctor_id', 'branch_id', 'weekday', 'start_time', 'end_time', 'slot_minutes', 'max_patients', 'is_active'];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'slot_minutes' => 'integer',
            'max_patients' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
