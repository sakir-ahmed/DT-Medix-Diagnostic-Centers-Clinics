<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Appointments\ChangeAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorQueueController extends Controller
{
    public function index(Request $request, BranchContext $context): Response
    {
        $doctor = Doctor::query()->where('user_id', $request->user()?->getAuthIdentifier())->first();

        $appointments = $doctor === null ? collect() : Appointment::query()
            ->with(['patient', 'doctor'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', now()->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value])
            ->orderBy('token_no')
            ->get();

        return Inertia::render('Admin/Doctor/Queue', [
            'doctor' => $doctor === null ? null : ['id' => $doctor->id, 'name' => $doctor->name],
            'appointments' => AppointmentResource::collection($appointments)->resolve(),
            'branch_id' => $context->currentBranchId(),
        ]);
    }

    public function next(Request $request, ChangeAppointmentStatusAction $change, BranchContext $context): RedirectResponse
    {
        $doctor = Doctor::query()->where('user_id', $request->user()?->getAuthIdentifier())->first();
        abort_if($doctor === null || $context->currentBranchId() === null, 404);

        $change->next($doctor->id, $context->currentBranchId());

        return back()->with('success', __('appointments.next_called'));
    }
}
