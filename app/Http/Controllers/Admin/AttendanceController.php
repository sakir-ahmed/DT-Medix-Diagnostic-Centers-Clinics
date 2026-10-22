<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hr\ImportAttendanceFileAction;
use App\Actions\Hr\RecalculateAttendanceAction;
use App\Actions\Hr\RecordManualAttendanceAction;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManualAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $view = $request->query('view') === 'month' ? 'month' : 'day';
        $date = $this->date((string) $request->query('date', ''));
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month', '')) === 1 ? (string) $request->query('month') : $date->format('Y-m');

        $employees = Employee::query()->where('is_active', true)->with('workShift:id,name,start_time,end_time')->orderBy('name')->limit(500)->get();

        return Inertia::render('Admin/Hr/Attendance', [
            'view' => $view,
            'date' => $date->toDateString(),
            'month' => $month,
            'statuses' => array_map(fn (AttendanceStatus $status): string => $status->value, AttendanceStatus::cases()),
            ...($view === 'day' ? $this->day($employees, $date) : $this->sheet($employees, $month)),
        ]);
    }

    public function store(ManualAttendanceRequest $request, RecordManualAttendanceAction $record): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);
        $record->execute($employee, $data, (int) $request->user()?->id);

        return back()->with('success', __('hr.attendance_saved'));
    }

    public function destroy(AttendanceRecord $record, RecordManualAttendanceAction $action): RedirectResponse
    {
        $action->revert($record);

        return back()->with('success', __('hr.attendance_reverted'));
    }

    public function import(Request $request, ImportAttendanceFileAction $import, BranchContext $context): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:5120', 'mimetypes:text/plain,text/csv,application/csv,application/octet-stream']]);

        $branchId = $context->currentBranchId();
        abort_if($branchId === null, 422);

        $result = $import->execute($branchId, (string) file_get_contents((string) $request->file('file')?->getRealPath()));

        return back()->with('success', __('hr.import_done', $result));
    }

    public function recalculate(Request $request, RecalculateAttendanceAction $recalculate): RedirectResponse
    {
        $date = $this->date((string) $request->input('date', ''));

        foreach (Employee::query()->where('is_active', true)->with('workShift')->get() as $employee) {
            $recalculate->execute($employee, $date, $date);
        }

        return back()->with('success', __('hr.recalculated'));
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    private function day($employees, CarbonImmutable $date): array
    {
        $records = AttendanceRecord::query()->whereDate('work_date', $date)->get()->keyBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($records): array {
            $record = $records->get($employee->id);

            return [
                'employee_id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'designation' => $employee->designation,
                'shift' => $employee->workShift?->name,
                'record_id' => $record?->id,
                'status' => $record?->status->value,
                'check_in' => $record?->check_in === null ? null : substr($record->check_in, 0, 5),
                'check_out' => $record?->check_out === null ? null : substr($record->check_out, 0, 5),
                'late_minutes' => $record?->late_minutes,
                'worked_minutes' => $record?->worked_minutes,
                'overtime_minutes' => $record?->overtime_minutes,
                'is_manual' => $record?->is_manual,
                'note' => $record?->note,
            ];
        })->values();

        $counts = [];
        foreach (AttendanceStatus::cases() as $status) {
            $counts[$status->value] = $rows->where('status', $status->value)->count();
        }
        $counts['unmarked'] = $rows->whereNull('status')->count();

        return ['rows' => $rows, 'counts' => $counts];
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    private function sheet($employees, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $records = AttendanceRecord::query()
            ->whereBetween('work_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($records): array {
            $mine = $records->get($employee->id, collect());
            $cells = $mine->mapWithKeys(fn (AttendanceRecord $record): array => [(int) $record->work_date->format('j') => $record->status->value])->all();

            return [
                'employee_id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'cells' => $cells,
                'present' => $mine->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::HalfDay])->count(),
                'late' => $mine->where('status', AttendanceStatus::Late)->count(),
                'absent' => $mine->where('status', AttendanceStatus::Absent)->count(),
                'leave' => $mine->where('status', AttendanceStatus::Leave)->count(),
                'overtime_minutes' => (int) $mine->sum('overtime_minutes'),
            ];
        })->values();

        return ['rows' => $rows, 'daysInMonth' => $start->daysInMonth];
    }

    private function date(string $value): CarbonImmutable
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? CarbonImmutable::parse($value)->startOfDay() : CarbonImmutable::today();
    }
}
