<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentUpdated;
use App\Exceptions\DomainException;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\SequenceGenerator;
use App\Services\SlotService;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Books a free slot under a lock on the doctor, issues a token number per
 * doctor, branch and day, and applies the follow-up window and fee.
 */
final class BookAppointmentAction
{
    public function __construct(private readonly SlotService $slots, private readonly SequenceGenerator $sequences) {}

    public function execute(Patient $patient, Doctor $doctor, int $branchId, Carbon $date, string $time, string $source = 'walk-in', ?string $notes = null, ?int $userId = null): Appointment
    {
        $appointment = DB::transaction(function () use ($patient, $doctor, $branchId, $date, $time, $source, $notes, $userId): Appointment {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->first();

            if (! $this->slots->isAvailable($doctor, $branchId, $date, $time)) {
                throw DomainException::make('SLOT_UNAVAILABLE', 409);
            }

            [$visitType, $fee] = $this->visitTypeAndFee($patient, $doctor, $date);
            $token = $this->sequences->next("token:{$branchId}:{$doctor->id}:{$date->toDateString()}");

            return Appointment::query()->create([
                'branch_id' => $branchId,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => $date->toDateString(),
                'start_time' => substr($time, 0, 5).':00',
                'token_no' => $token,
                'visit_type' => $visitType,
                'status' => AppointmentStatus::Booked,
                'source' => $source,
                'fee' => $fee,
                'notes' => $notes,
                'booked_by' => $userId,
            ]);
        });

        AppointmentUpdated::dispatch($appointment);

        return $appointment;
    }

    /**
     * A visit within the follow-up window after a completed consultation with
     * the same doctor is a follow-up with the follow-up fee.
     *
     * @return array{0: string, 1: string}
     */
    private function visitTypeAndFee(Patient $patient, Doctor $doctor, Carbon $date): array
    {
        $days = $doctor->follow_up_days ?? (int) (tenant()?->setting('follow_up_days') ?? 7);

        $recent = Appointment::query()->withoutGlobalScope('branch')
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', AppointmentStatus::Completed->value)
            ->whereDate('appointment_date', '>=', $date->copy()->subDays($days)->toDateString())
            ->whereDate('appointment_date', '<', $date->toDateString())
            ->exists();

        if (! $recent) {
            return ['new', Money::of((string) $doctor->consultation_fee)];
        }

        $fee = Money::isZero((string) $doctor->follow_up_fee)
            ? Money::percent((string) $doctor->consultation_fee, (string) (tenant()?->setting('follow_up_fee_percent') ?? 0))
            : Money::of((string) $doctor->follow_up_fee);

        return ['follow_up', $fee];
    }
}
