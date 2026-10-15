<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use Illuminate\Support\Carbon;

/**
 * Appointment slots from the doctor's weekly schedule at a branch, minus
 * leave days, booked slots and the per-session patient limit.
 */
final class SlotService
{
    /**
     * @return list<array{time: string, available: bool, booked: bool}>
     */
    public function slots(Doctor $doctor, int $branchId, Carbon $date): array
    {
        if (! $doctor->is_active || $this->onLeave($doctor, $date)) {
            return [];
        }

        $schedules = DoctorSchedule::query()->withoutGlobalScope('branch')
            ->where('doctor_id', $doctor->id)
            ->where('branch_id', $branchId)
            ->where('weekday', $date->dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $booked = Appointment::query()->withoutGlobalScope('branch')
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->pluck('start_time')
            ->map(fn (string $time): string => substr($time, 0, 5))
            ->all();

        $now = now();
        $slots = [];

        foreach ($schedules as $schedule) {
            $minutes = $schedule->slot_minutes ?? $doctor->slot_minutes;
            $start = Carbon::parse($date->toDateString().' '.$schedule->start_time);
            $end = Carbon::parse($date->toDateString().' '.$schedule->end_time);
            $sessionBooked = 0;
            $sessionSlots = [];

            for ($time = $start->copy(); $time->copy()->addMinutes($minutes)->lte($end); $time->addMinutes($minutes)) {
                $label = $time->format('H:i');
                $isBooked = in_array($label, $booked, true);
                $sessionBooked += $isBooked ? 1 : 0;
                $sessionSlots[] = ['time' => $label, 'available' => ! $isBooked && $time->gt($now), 'booked' => $isBooked];
            }

            if ($schedule->max_patients !== null && $sessionBooked >= $schedule->max_patients) {
                $sessionSlots = array_map(fn (array $slot): array => ['available' => false] + $slot, $sessionSlots);
            }

            $slots = [...$slots, ...$sessionSlots];
        }

        return $slots;
    }

    public function isAvailable(Doctor $doctor, int $branchId, Carbon $date, string $time): bool
    {
        foreach ($this->slots($doctor, $branchId, $date) as $slot) {
            if ($slot['time'] === substr($time, 0, 5)) {
                return $slot['available'];
            }
        }

        return false;
    }

    private function onLeave(Doctor $doctor, Carbon $date): bool
    {
        return DoctorLeave::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->exists();
    }
}
