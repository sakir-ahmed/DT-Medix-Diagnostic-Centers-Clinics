<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceCalculator;
use Carbon\CarbonImmutable;

/**
 * Builds the attendance record of one employee-day from raw punches, the
 * shift, holidays and approved leave. Manual records are left untouched.
 */
final class CalculateAttendanceDayAction
{
    public function __construct(private readonly AttendanceCalculator $calculator) {}

    public function execute(Employee $employee, CarbonImmutable $date): ?AttendanceRecord
    {
        $existing = AttendanceRecord::query()->where('employee_id', $employee->id)->whereDate('work_date', $date)->first();

        if ($existing?->is_manual) {
            return $existing;
        }

        if ($date->startOfDay()->isFuture() || $date->lessThan(CarbonImmutable::parse($employee->joined_on)->startOfDay())) {
            return null;
        }

        $punches = AttendancePunch::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('punched_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('punched_at')
            ->pluck('punched_at');

        $shift = $employee->workShift;
        $checkIn = $punches->first()?->format('H:i:s');
        $checkOut = $punches->count() > 1 ? $punches->last()->format('H:i:s') : null;
        $metrics = $this->calculator->metrics($shift, $date, $checkIn, $checkOut);

        if ($punches->isEmpty()) {
            $status = $this->statusWithoutPunches($employee, $date);
        } elseif ($checkOut !== null && $metrics['worked'] < AttendanceCalculator::HALF_DAY_MINUTES) {
            $status = AttendanceStatus::HalfDay;
        } else {
            $status = $metrics['late'] > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;
        }

        return AttendanceRecord::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $date->toDateString()],
            [
                'branch_id' => $employee->branch_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'late_minutes' => $metrics['late'],
                'early_minutes' => $metrics['early'],
                'worked_minutes' => $metrics['worked'],
                'overtime_minutes' => $metrics['overtime'],
                'source' => $punches->isEmpty() ? 'system' : 'device',
                'is_manual' => false,
            ],
        );
    }

    private function statusWithoutPunches(Employee $employee, CarbonImmutable $date): AttendanceStatus
    {
        if (Holiday::query()->whereDate('holiday_on', $date)->exists()) {
            return AttendanceStatus::Holiday;
        }

        if ($employee->workShift?->isWeeklyOff($date->dayOfWeek)) {
            return AttendanceStatus::WeeklyOff;
        }

        $onLeave = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();

        return $onLeave ? AttendanceStatus::Leave : AttendanceStatus::Absent;
    }
}
