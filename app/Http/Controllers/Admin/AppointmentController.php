<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Appointments\ChangeAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\AppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\SlotService;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request, SlotService $slots, BranchContext $context): Response
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()))->startOfDay();
        $doctorId = $request->query('doctor_id');
        $branchId = $context->currentBranchId();

        $appointments = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereDate('appointment_date', $date->toDateString())
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->orderBy('token_no')
            ->get();

        $doctor = $doctorId === null ? null : Doctor::query()->whereKey($doctorId)->first();

        return Inertia::render('Admin/Appointments/Index', [
            'date' => $date->toDateString(),
            'doctor_id' => $doctor?->id,
            'doctors' => Doctor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn (Doctor $row): array => [
                'value' => $row->id,
                'label' => $row->name,
            ])->values(),
            'appointments' => AppointmentResource::collection($appointments)->resolve(),
            'slots' => $doctor === null || $branchId === null ? [] : $slots->slots($doctor, $branchId, $date),
            'branch_id' => $branchId,
        ]);
    }

    public function store(AppointmentRequest $request, BookAppointmentAction $book, BranchContext $context): RedirectResponse
    {
        $branch = Branch::query()->whereKey($context->currentBranchId())->where('is_active', true)->first();
        abort_if($branch === null, 404);

        $patient = Patient::query()->findOrFail($request->integer('patient_id'));
        $doctor = Doctor::query()->findOrFail($request->integer('doctor_id'));
        $userId = $request->user()?->getAuthIdentifier();

        $book->execute(
            $patient,
            $doctor,
            $branch->id,
            Carbon::parse($request->string('date')->toString()),
            $request->string('time')->toString(),
            $request->string('source')->toString() ?: 'walk-in',
            $request->input('notes'),
            is_numeric($userId) ? (int) $userId : null,
        );

        return back()->with('success', __('appointments.booked'));
    }

    public function updateStatus(AppointmentStatusRequest $request, Appointment $appointment, ChangeAppointmentStatusAction $change): RedirectResponse
    {
        $change->execute($appointment, AppointmentStatus::from($request->string('status')->toString()), $request->input('reason'));

        return back()->with('success', __('appointments.status_updated'));
    }
}
