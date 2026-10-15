<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $specialty
 * @property string|null $qualifications
 * @property string|null $designation
 * @property string|null $bmdc_no
 * @property string|null $mobile
 * @property string|null $email
 * @property string $consultation_fee
 * @property string $follow_up_fee
 * @property int|null $follow_up_days
 * @property int $slot_minutes
 * @property bool $is_visiting
 * @property bool $is_active
 * @property string|null $signature_path
 */
class Doctor extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DoctorFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'name_bn', 'specialty', 'qualifications', 'designation', 'bmdc_no', 'mobile', 'email',
        'consultation_fee', 'follow_up_fee', 'follow_up_days', 'slot_minutes', 'is_visiting', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consultation_fee' => 'decimal:2',
            'follow_up_fee' => 'decimal:2',
            'follow_up_days' => 'integer',
            'slot_minutes' => 'integer',
            'is_visiting' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'bmdc_no', 'consultation_fee', 'follow_up_fee', 'is_active', 'user_id'])
            ->logOnlyDirty();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<DoctorSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    /**
     * @return HasMany<DoctorLeave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(DoctorLeave::class);
    }
}
