<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Doctors\ManageScheduleAction;
use App\Actions\Doctors\SaveDoctorAction;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorRequest;
use App\Http\Requests\Admin\LeaveRequest;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $doctors = Doctor::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$search}%")->orWhere('specialty', 'like', "%{$search}%")->orWhere('bmdc_no', $search)))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Doctors/Index', [
            'doctors' => DoctorResource::collection($doctors),
            'filters' => ['q' => $search],
            'doctorUsers' => $this->doctorUsers(),
        ]);
    }

    public function show(Doctor $doctor): Response
    {
        $doctor->load(['schedules' => fn ($q) => $q->with('branch:id,code,name')->orderBy('weekday')->orderBy('start_time'), 'leaves' => fn ($q) => $q->where('end_date', '>=', now()->subDays(30)->toDateString())->orderBy('start_date')]);

        return Inertia::render('Admin/Doctors/Show', [
            'doctor' => (new DoctorResource($doctor))->resolve(),
            'schedules' => $doctor->schedules->map(fn (DoctorSchedule $s): array => [
                'id' => $s->id,
                'branch' => $s->branch?->code.' - '.$s->branch?->name,
                'weekday' => $s->weekday,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'slot_minutes' => $s->slot_minutes ?? $doctor->slot_minutes,
                'max_patients' => $s->max_patients,
            ]),
            'leaves' => $doctor->leaves->map(fn (DoctorLeave $l): array => [
                'id' => $l->id,
                'start_date' => $l->start_date->toDateString(),
                'end_date' => $l->end_date->toDateString(),
                'reason' => $l->reason,
            ]),
            'branches' => Branch::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'signatureUrl' => $doctor->signature_path === null ? null : route('admin.doctors.signature', $doctor),
        ]);
    }

    public function store(DoctorRequest $request, SaveDoctorAction $save): RedirectResponse
    {
        $doctor = $save->execute($request->safe()->except('signature'), null, $request->file('signature'));

        return redirect()->route('admin.doctors.show', $doctor)->with('success', __('doctors.created'));
    }

    public function update(DoctorRequest $request, Doctor $doctor, SaveDoctorAction $save): RedirectResponse
    {
        $save->execute($request->safe()->except('signature'), $doctor, $request->file('signature'));

        return back()->with('success', __('doctors.updated'));
    }

    public function signature(Doctor $doctor): StreamedResponse
    {
        abort_if($doctor->signature_path === null, 404);

        return Storage::disk((string) config('center.storage_disk'))->response($doctor->signature_path);
    }

    public function storeSchedule(ScheduleRequest $request, Doctor $doctor, ManageScheduleAction $schedules): RedirectResponse
    {
        /** @var array{branch_id: int, weekday: int, start_time: string, end_time: string, slot_minutes?: int|null, max_patients?: int|null} $data */
        $data = $request->validated();
        $schedules->addSchedule($doctor, $data);

        return back()->with('success', __('doctors.schedule_added'));
    }

    public function destroySchedule(Doctor $doctor, DoctorSchedule $schedule, ManageScheduleAction $schedules): RedirectResponse
    {
        abort_unless($schedule->doctor_id === $doctor->id, 404);
        $schedules->removeSchedule($schedule);

        return back()->with('success', __('doctors.schedule_removed'));
    }

    public function storeLeave(LeaveRequest $request, Doctor $doctor, ManageScheduleAction $schedules): RedirectResponse
    {
        /** @var array{start_date: string, end_date: string, reason?: string|null} $data */
        $data = $request->validated();
        $schedules->addLeave($doctor, $data);

        return back()->with('success', __('doctors.leave_added'));
    }

    public function destroyLeave(Doctor $doctor, DoctorLeave $leave, ManageScheduleAction $schedules): RedirectResponse
    {
        abort_unless($leave->doctor_id === $doctor->id, 404);
        $schedules->removeLeave($leave);

        return back()->with('success', __('doctors.leave_removed'));
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function doctorUsers(): array
    {
        return User::role(RoleName::Doctor->value)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $u): array => ['value' => $u->id, 'label' => $u->name])->values()->all();
    }
}
