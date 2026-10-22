<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Support\Money;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $user_id
 * @property int|null $work_shift_id
 * @property string $code
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $designation
 * @property string|null $department
 * @property string|null $mobile
 * @property Carbon $joined_on
 * @property string|null $device_pin
 * @property string $basic_salary
 * @property string $house_allowance
 * @property string $medical_allowance
 * @property string $transport_allowance
 * @property string $other_allowance
 * @property string $overtime_rate
 * @property string|null $payment_method
 * @property string|null $payment_account
 * @property string|null $notes
 * @property bool $is_active
 */
class Employee extends Model
{
    use BelongsToBranch;

    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'user_id', 'work_shift_id', 'code', 'name', 'name_bn', 'designation', 'department', 'mobile', 'joined_on', 'device_pin',
        'basic_salary', 'house_allowance', 'medical_allowance', 'transport_allowance', 'other_allowance', 'overtime_rate',
        'payment_method', 'payment_account', 'notes', 'is_active',
    ];

    protected $hidden = ['payment_account'];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date:Y-m-d',
            'basic_salary' => 'decimal:2',
            'house_allowance' => 'decimal:2',
            'medical_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'payment_account' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkShift, $this>
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Basic salary plus all allowances.
     */
    public function grossSalary(): string
    {
        return Money::add($this->basic_salary, $this->house_allowance, $this->medical_allowance, $this->transport_allowance, $this->other_allowance);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'designation', 'basic_salary', 'house_allowance', 'medical_allowance', 'transport_allowance', 'other_allowance', 'work_shift_id', 'is_active'])->logOnlyDirty();
    }
}
