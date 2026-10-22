<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Models\Employee;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Recalculates a date range for one employee (manual records are skipped).
 */
final class RecalculateAttendanceAction
{
    public function __construct(private readonly CalculateAttendanceDayAction $day) {}

    public function execute(Employee $employee, CarbonInterface $from, CarbonInterface $to): void
    {
        $employee->loadMissing('workShift');
        $cursor = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $this->day->execute($employee, $cursor);
            $cursor = $cursor->addDay();
        }
    }
}
