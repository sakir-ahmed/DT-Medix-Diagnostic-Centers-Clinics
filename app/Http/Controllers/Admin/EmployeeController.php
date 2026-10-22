<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hr\SaveEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    private const SALARY_FIELDS = ['basic_salary', 'house_allowance', 'medical_allowance', 'transport_allowance', 'other_allowance', 'overtime_rate', 'payment_method', 'payment_account'];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $canSeePay = (bool) $request->user()?->can('payroll.view');

        $employees = Employee::query()
            ->with(['workShift:id,name', 'branch:id,code'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$search}%")->orWhere('code', $search)->orWhere('device_pin', $search)))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString()
            ->through(fn (Employee $employee): array => [
                'id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->name,
                'name_bn' => $employee->name_bn,
                'designation' => $employee->designation,
                'department' => $employee->department,
                'mobile' => $employee->mobile === null ? null : substr($employee->mobile, 2),
                'joined_on' => $employee->joined_on->toDateString(),
                'device_pin' => $employee->device_pin,
                'work_shift_id' => $employee->work_shift_id,
                'shift' => $employee->workShift?->name,
                'branch' => $employee->branch?->code,
                'notes' => $employee->notes,
                'is_active' => $employee->is_active,
                ...($canSeePay ? [
                    'basic_salary' => $employee->basic_salary,
                    'house_allowance' => $employee->house_allowance,
                    'medical_allowance' => $employee->medical_allowance,
                    'transport_allowance' => $employee->transport_allowance,
                    'other_allowance' => $employee->other_allowance,
                    'overtime_rate' => $employee->overtime_rate,
                    'gross' => $employee->grossSalary(),
                    'payment_method' => $employee->payment_method,
                    'payment_account' => $employee->payment_account,
                ] : []),
            ]);

        return Inertia::render('Admin/Hr/Employees', [
            'employees' => $employees,
            'shifts' => WorkShift::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => ['q' => $search],
            'canSeePay' => $canSeePay,
        ]);
    }

    public function store(EmployeeRequest $request, SaveEmployeeAction $save): RedirectResponse
    {
        $save->execute($this->payload($request));

        return back()->with('success', __('hr.employee_created'));
    }

    public function update(EmployeeRequest $request, Employee $employee, SaveEmployeeAction $save): RedirectResponse
    {
        $save->execute($this->payload($request), $employee);

        return back()->with('success', __('hr.employee_updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EmployeeRequest $request): array
    {
        $data = $request->validated();

        if (! $request->user()?->can('payroll.view')) {
            $data = array_diff_key($data, array_flip(self::SALARY_FIELDS));
        }

        foreach (['basic_salary', 'house_allowance', 'medical_allowance', 'transport_allowance', 'other_allowance', 'overtime_rate'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '0.00';
            }
        }

        return $data;
    }
}
