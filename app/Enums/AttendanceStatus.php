<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case HalfDay = 'half_day';
    case Absent = 'absent';
    case Leave = 'leave';
    case WeeklyOff = 'weekly_off';
    case Holiday = 'holiday';

    /**
     * Counts as a paid working day in payroll.
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::Present, self::Late, self::Leave, self::WeeklyOff, self::Holiday], true);
    }
}
