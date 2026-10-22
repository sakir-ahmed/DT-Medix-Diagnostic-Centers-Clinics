<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\AttendanceCalculator;
use Carbon\CarbonImmutable;

/**
 * Saves a hand-entered attendance day. It is flagged manual, so device
 * punches arriving later never overwrite it.
 */
final class RecordManualAttendanceAction
{
    public function __construct(private readonly AttendanceCalculator $calculator, private readonly CalculateAttendanceDayAction $day) {}

    /**
     * @param  array{work_date: string, status: string, check_in?: string|null, check_out?: string|null, note?: string|null}  $data
     */
    public function execute(Employee $employee, array $data, int $userId): AttendanceRecord
    {
        $date = CarbonImmutable::parse($data['work_date'])->startOfDay();
        $status = AttendanceStatus::from($data['status']);
        $checkIn = filled($data['check_in'] ?? null) ? $data['check_in'].':00' : null;
        $checkOut = filled($data['check_out'] ?? null) ? $data['check_out'].':00' : null;
        $metrics = $this->calculator->metrics($employee->workShift, $date, $checkIn, $checkOut);

        return AttendanceRecord::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'work_date' => $date->toDateString()],
            [
                'branch_id' => $employee->branch_id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'late_minutes' => $status === AttendanceStatus::Late ? $metrics['late'] : 0,
                'early_minutes' => $metrics['early'],
                'worked_minutes' => $metrics['worked'],
                'overtime_minutes' => $metrics['overtime'],
                'source' => 'manual',
                'is_manual' => true,
                'note' => $data['note'] ?? null,
                'edited_by' => $userId,
            ],
        );
    }

    /**
     * Drops the manual flag and rebuilds the day from punches.
     */
    public function revert(AttendanceRecord $record): void
    {
        $employee = $record->employee()->firstOrFail();
        $date = CarbonImmutable::parse($record->work_date);
        $record->delete();
        $this->day->execute($employee, $date);
    }
}
