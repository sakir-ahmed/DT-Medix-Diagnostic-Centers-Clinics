<?php

declare(strict_types=1);

namespace App\Actions\Doctors;

use App\Exceptions\DomainException;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;

/**
 * Weekly schedules per branch and leave days. Overlapping schedule rows
 * for the same doctor and weekday are rejected.
 */
final class ManageScheduleAction
{
    /**
     * @param  array{branch_id: int, weekday: int, start_time: string, end_time: string, slot_minutes?: int|null, max_patients?: int|null}  $data
     */
    public function addSchedule(Doctor $doctor, array $data): DoctorSchedule
    {
        $overlap = DoctorSchedule::query()
            ->withoutGlobalScope('branch')
            ->where('doctor_id', $doctor->id)
            ->where('weekday', $data['weekday'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        if ($overlap) {
            throw DomainException::make('SCHEDULE_OVERLAP', 422);
        }

        return $doctor->schedules()->create($data);
    }

    public function removeSchedule(DoctorSchedule $schedule): void
    {
        $schedule->delete();
    }

    /**
     * @param  array{start_date: string, end_date: string, reason?: string|null}  $data
     */
    public function addLeave(Doctor $doctor, array $data): DoctorLeave
    {
        return $doctor->leaves()->create($data);
    }

    public function removeLeave(DoctorLeave $leave): void
    {
        $leave->delete();
    }
}
