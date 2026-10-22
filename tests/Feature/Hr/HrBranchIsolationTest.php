<?php

declare(strict_types=1);

use App\Actions\Hr\GeneratePayrollRunAction;
use App\Actions\Hr\RecordManualAttendanceAction;
use App\Enums\RoleName;
use App\Models\AttendanceDevice;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\WorkShift;

beforeEach(function (): void {
    makeCenter();
    $this->userId = makeUser(RoleName::Owner)->id;
    $this->travelTo('2026-10-22 12:00:00');
    $this->branchA = mainBranch();
    $this->branchB = makeBranch('BR02');
    $this->employeeB = Employee::factory()->create(['branch_id' => $this->branchB->id, 'joined_on' => '2026-01-01']);
    app(RecordManualAttendanceAction::class)->execute($this->employeeB, ['work_date' => '2026-10-21', 'status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00'], (int) test()->userId);
    $this->record = AttendanceRecord::query()->withoutGlobalScopes()->firstOrFail();
    $this->leave = LeaveRequest::query()->withoutGlobalScopes()->create(['branch_id' => $this->branchB->id, 'employee_id' => $this->employeeB->id, 'type' => 'casual', 'from_date' => '2026-10-25', 'to_date' => '2026-10-25', 'days' => 1, 'status' => 'pending']);
    $this->run = app(GeneratePayrollRunAction::class)->execute($this->branchB->id, '2026-09');
    $this->slip = Payslip::query()->withoutGlobalScopes()->firstOrFail();
    $this->device = AttendanceDevice::factory()->create(['branch_id' => $this->branchB->id]);
    $this->shift = WorkShift::factory()->create(['branch_id' => $this->branchB->id]);
    $this->managerA = makeUser(RoleName::BranchManager, $this->branchA);
});

it('returns 404 for another branch employee, record, leave, device and shift', function (): void {
    $this->actingAs($this->managerA);

    $this->put(route('admin.employees.update', $this->employeeB), ['name' => 'X', 'joined_on' => '2026-01-01'])->assertNotFound();
    $this->delete(route('admin.attendance.destroy', $this->record))->assertNotFound();
    $this->post(route('admin.leaves.approve', $this->leave))->assertNotFound();
    $this->put(route('admin.hr-setup.devices.update', $this->device), ['name' => 'X', 'serial_number' => 'Z9'])->assertNotFound();
    $this->put(route('admin.hr-setup.shifts.update', $this->shift), ['name' => 'X', 'start_time' => '09:00', 'end_time' => '17:00', 'grace_minutes' => 5])->assertNotFound();
});

it('hides other branches from lists', function (): void {
    $this->actingAs($this->managerA)->get(route('admin.employees.index'))->assertInertia(fn ($page) => $page->has('employees.data', 0));
    $this->actingAs($this->managerA)->get(route('admin.leaves.index'))->assertInertia(fn ($page) => $page->has('leaves.data', 0));
    $this->actingAs($this->managerA)->get(route('admin.attendance.index', ['date' => '2026-10-21']))->assertInertia(fn ($page) => $page->has('rows', 0));
});

it('returns 404 for another branch payroll and payslip', function (): void {
    // Accountants see all branches by design (branches.all); a branch-restricted user must not.
    $restricted = makeUser(RoleName::BranchManager, $this->branchA);
    $restricted->givePermissionTo(['payroll.view', 'payroll.manage']);

    $this->actingAs($restricted)->get(route('admin.payroll.show', $this->run))->assertNotFound();
    $this->actingAs($restricted)->get(route('admin.payslips.show', $this->slip))->assertNotFound();
    $this->actingAs($restricted)->post(route('admin.payroll.finalize', $this->run))->assertNotFound();
    $this->actingAs($restricted)->post(route('admin.payslips.pay', $this->slip), ['payment_method' => 'cash'])->assertNotFound();
    $this->actingAs($restricted)->get(route('admin.payroll.index'))->assertInertia(fn ($page) => $page->has('runs', 0));

    expect(PayrollRun::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('keeps every role without HR permission out', function (): void {
    foreach ([RoleName::Receptionist, RoleName::LabTechnician, RoleName::Doctor] as $role) {
        $this->actingAs(makeUser($role, $this->branchA))->get(route('admin.employees.index'))->assertForbidden();
    }
});
