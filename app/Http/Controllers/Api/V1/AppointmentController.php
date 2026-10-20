<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Appointments\ChangeAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\AppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @tags Appointments
 */
class AppointmentController extends Controller
{
    /**
     * Free slots for a doctor on a date.
     */
    public function slots(Branch $branch, Doctor $doctor, Request $request, SlotService $slots): JsonResponse
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()));

        return ApiResponse::success($slots->slots($doctor, $branch->id, $date));
    }

    /**
     * Book a slot. Requires an Idempotency-Key.
     */
    public function store(Branch $branch, AppointmentRequest $request, BookAppointmentAction $book): JsonResponse
    {
        $patient = Patient::query()->findOrFail($request->integer('patient_id'));
        $doctor = Doctor::query()->findOrFail($request->integer('doctor_id'));
        $userId = $request->user()?->getAuthIdentifier();

        $appointment = $book->execute(
            $patient,
            $doctor,
            $branch->id,
            Carbon::parse($request->string('date')->toString()),
            $request->string('time')->toString(),
            $request->string('source')->toString() ?: 'walk-in',
            $request->input('notes'),
            is_numeric($userId) ? (int) $userId : null,
        );

        return ApiResponse::success(new AppointmentResource($appointment->load(['patient', 'doctor'])), __('appointments.booked'), 201);
    }

    public function updateStatus(Branch $branch, Appointment $appointment, AppointmentStatusRequest $request, ChangeAppointmentStatusAction $change): JsonResponse
    {
        abort_if($appointment->branch_id !== $branch->id, 404);
        $appointment = $change->execute($appointment, AppointmentStatus::from($request->string('status')->toString()), $request->input('reason'));

        return ApiResponse::success(new AppointmentResource($appointment->load(['patient', 'doctor'])), __('appointments.status_updated'));
    }

    public function queue(Branch $branch, Request $request): JsonResponse
    {
        $doctor = Doctor::query()->where('user_id', $request->user()?->getAuthIdentifier())->first();
        abort_if($doctor === null, 404);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->where('branch_id', $branch->id)
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', now()->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value])
            ->orderBy('token_no')
            ->get();

        return ApiResponse::success(AppointmentResource::collection($appointments));
    }
}
