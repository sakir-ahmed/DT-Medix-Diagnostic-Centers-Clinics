<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One employee-day. Manual records are never recalculated from punches.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $employee_id
 * @property Carbon $work_date
 * @property string|null $check_in
 * @property string|null $check_out
 * @property AttendanceStatus $status
 * @property int $late_minutes
 * @property int $early_minutes
 * @property int $worked_minutes
 * @property int $overtime_minutes
 * @property string $source
 * @property bool $is_manual
 * @property string|null $note
 */
class AttendanceRecord extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'employee_id', 'work_date', 'check_in', 'check_out', 'status', 'late_minutes', 'early_minutes',
        'worked_minutes', 'overtime_minutes', 'source', 'is_manual', 'note', 'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
            'status' => AttendanceStatus::class,
            'is_manual' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
