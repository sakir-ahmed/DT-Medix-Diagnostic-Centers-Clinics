<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $employee_id
 * @property LeaveType $type
 * @property Carbon $from_date
 * @property Carbon $to_date
 * @property int $days
 * @property string|null $reason
 * @property LeaveStatus $status
 */
class LeaveRequest extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'employee_id', 'type', 'from_date', 'to_date', 'days', 'reason', 'status', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return [
            'type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'from_date' => 'date:Y-m-d',
            'to_date' => 'date:Y-m-d',
            'decided_at' => 'datetime',
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
