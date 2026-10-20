<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $doctor_id
 * @property int $patient_id
 * @property Carbon $appointment_date
 * @property string $start_time
 * @property int $token_no
 * @property string $visit_type
 * @property AppointmentStatus $status
 * @property string $source
 * @property string $fee
 * @property string|null $notes
 * @property string|null $cancel_reason
 * @property int|null $booked_by
 * @property Carbon|null $arrived_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read Doctor $doctor
 * @property-read Patient $patient
 */
class Appointment extends Model
{
    use BelongsToBranch;
    use LogsActivity;

    protected $fillable = ['branch_id', 'doctor_id', 'patient_id', 'appointment_date', 'start_time', 'token_no', 'visit_type', 'status', 'source', 'fee', 'notes', 'booked_by'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'token_no' => 'integer',
            'status' => AppointmentStatus::class,
            'fee' => 'decimal:2',
            'arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'appointment_date', 'start_time', 'doctor_id'])->logOnlyDirty();
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
