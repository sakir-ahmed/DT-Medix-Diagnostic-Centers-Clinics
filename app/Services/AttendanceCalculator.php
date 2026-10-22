<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WorkShift;
use Carbon\CarbonImmutable;

/**
 * Turns a check-in and check-out time into late, early, worked and
 * overtime minutes for a shift. Pure calculation, no database access.
 */
final class AttendanceCalculator
{
    /** Minutes worked below which a day counts as a half day. */
    public const HALF_DAY_MINUTES = 240;

    /**
     * @return array{late: int, early: int, worked: int, overtime: int}
     */
    public function metrics(?WorkShift $shift, CarbonImmutable $date, ?string $checkIn, ?string $checkOut): array
    {
        $in = $checkIn === null ? null : $this->at($date, $checkIn);
        $out = $checkOut === null ? null : $this->at($date, $checkOut);

        $metrics = ['late' => 0, 'early' => 0, 'worked' => 0, 'overtime' => 0];

        if ($in !== null && $out !== null && $out->greaterThan($in)) {
            $metrics['worked'] = (int) $in->diffInMinutes($out);
        }

        if ($shift === null) {
            return $metrics;
        }

        $start = $this->at($date, $shift->start_time);
        $end = $this->at($date, $shift->end_time);

        if ($in !== null && $in->greaterThan($start->addMinutes($shift->grace_minutes))) {
            $metrics['late'] = (int) $start->diffInMinutes($in);
        }

        if ($out !== null && $out->lessThan($end)) {
            $metrics['early'] = (int) $out->diffInMinutes($end);
        }

        if ($out !== null && $out->greaterThan($end)) {
            $metrics['overtime'] = (int) $end->diffInMinutes($out);
        }

        return $metrics;
    }

    private function at(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return $date->setTimeFromTimeString($time);
    }
}
