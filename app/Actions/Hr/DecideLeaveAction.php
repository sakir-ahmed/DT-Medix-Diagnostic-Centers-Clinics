<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Exceptions\DomainException;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;

final class DecideLeaveAction
{
    public function __construct(private readonly RecalculateAttendanceAction $recalculate) {}

    public function execute(LeaveRequest $leave, bool $approve, int $userId): LeaveRequest
    {
        if ($leave->status !== LeaveStatus::Pending) {
            throw DomainException::make('LEAVE_ALREADY_DECIDED');
        }

        $leave->forceFill([
            'status' => $approve ? LeaveStatus::Approved : LeaveStatus::Rejected,
            'decided_by' => $userId,
            'decided_at' => now(),
        ])->save();

        if ($approve) {
            // Days already marked absent by the system become leave days.
            AttendanceRecord::query()
                ->where('employee_id', $leave->employee_id)
                ->where('is_manual', false)
                ->where('status', AttendanceStatus::Absent)
                ->whereBetween('work_date', [$leave->from_date, $leave->to_date])
                ->delete();

            $this->recalculate->execute($leave->employee()->firstOrFail(), CarbonImmutable::parse($leave->from_date), CarbonImmutable::parse($leave->to_date));
        }

        return $leave;
    }
}
