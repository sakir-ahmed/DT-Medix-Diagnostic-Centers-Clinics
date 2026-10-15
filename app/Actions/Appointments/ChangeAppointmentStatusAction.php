<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentUpdated;
use App\Exceptions\DomainException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

/**
 * booked -> arrived -> in-consultation -> completed, plus no-show and cancelled.
 */
final class ChangeAppointmentStatusAction
{
    public function execute(Appointment $appointment, AppointmentStatus $status, ?string $reason = null): Appointment
    {
        if (! in_array($status, $appointment->status->next(), true)) {
            throw DomainException::make('INVALID_STATE', 409);
        }

        if ($status === AppointmentStatus::Cancelled && blank($reason)) {
            throw DomainException::make('REASON_REQUIRED', 422);
        }

        $appointment->status = $status;
        match ($status) {
            AppointmentStatus::Arrived => $appointment->arrived_at = now(),
            AppointmentStatus::InConsultation => $appointment->started_at = now(),
            AppointmentStatus::Completed => $appointment->completed_at = now(),
            default => null,
        };

        if ($status === AppointmentStatus::Cancelled) {
            $appointment->cancel_reason = $reason;
        }

        $appointment->save();
        AppointmentUpdated::dispatch($appointment);

        return $appointment;
    }

    /**
     * Doctor's "Next patient": finish the current consultation and call the
     * next arrived patient by token.
     */
    public function next(int $doctorId, int $branchId): ?Appointment
    {
        return DB::transaction(function () use ($doctorId, $branchId): ?Appointment {
            $today = now()->toDateString();
            $base = fn () => Appointment::query()->withoutGlobalScope('branch')
                ->where('doctor_id', $doctorId)->where('branch_id', $branchId)->whereDate('appointment_date', $today);

            foreach ($base()->where('status', AppointmentStatus::InConsultation->value)->get() as $current) {
                $this->execute($current, AppointmentStatus::Completed);
            }

            $next = $base()->where('status', AppointmentStatus::Arrived->value)->orderBy('token_no')->first();

            return $next === null ? null : $this->execute($next, AppointmentStatus::InConsultation);
        });
    }
}
