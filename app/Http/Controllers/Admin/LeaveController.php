<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hr\DecideLeaveAction;
use App\Actions\Hr\RequestLeaveAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $leaves = LeaveRequest::query()
            ->with('employee:id,code,name')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('from_date')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString()
            ->through(fn (LeaveRequest $leave): array => [
                'id' => $leave->id,
                'employee' => $leave->employee?->name,
                'code' => $leave->employee?->code,
                'type' => $leave->type->value,
                'from_date' => $leave->from_date->toDateString(),
                'to_date' => $leave->to_date->toDateString(),
                'days' => $leave->days,
                'reason' => $leave->reason,
                'status' => $leave->status->value,
            ]);

        return Inertia::render('Admin/Hr/Leaves', [
            'leaves' => $leaves,
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'filters' => ['status' => $request->query('status')],
        ]);
    }

    public function store(LeaveRequestRequest $request, RequestLeaveAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->execute(Employee::query()->findOrFail($data['employee_id']), $data);

        return back()->with('success', __('hr.leave_requested'));
    }

    public function approve(Request $request, LeaveRequest $leave, DecideLeaveAction $action): RedirectResponse
    {
        $action->execute($leave, true, (int) $request->user()?->id);

        return back()->with('success', __('hr.leave_approved'));
    }

    public function reject(Request $request, LeaveRequest $leave, DecideLeaveAction $action): RedirectResponse
    {
        $action->execute($leave, false, (int) $request->user()?->id);

        return back()->with('success', __('hr.leave_rejected'));
    }
}
