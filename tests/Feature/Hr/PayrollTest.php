<?php

declare(strict_types=1);

use App\Actions\Hr\FinalizePayrollRunAction;
use App\Actions\Hr\GeneratePayrollRunAction;
use App\Actions\Hr\RecordManualAttendanceAction;
use App\Enums\PayrollStatus;
use App\Enums\RoleName;
use App\Exceptions\DomainException;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    makeCenter();
    $this->userId = makeUser(RoleName::Owner)->id;
    $this->travelTo('2026-10-22 12:00:00');
    $this->branch = mainBranch();
    $this->employee = Employee::factory()->create([
        'branch_id' => $this->branch->id, 'joined_on' => '2026-01-01', 'work_shift_id' => null,
        'basic_salary' => '15000.00', 'house_allowance' => '3000.00', 'medical_allowance' => '1000.00', 'transport_allowance' => '1000.00', 'overtime_rate' => '120.00',
    ]);
});

function fillSeptember(Employee $employee, array $absentDays = [], array $overtime = []): void
{
    $record = app(RecordManualAttendanceAction::class);

    for ($day = 1; $day <= 30; $day++) {
        $date = CarbonImmutable::create(2026, 9, $day)->toDateString();
        $record->execute($employee, [
            'work_date' => $date,
            'status' => in_array($day, $absentDays, true) ? 'absent' : 'present',
            'check_in' => in_array($day, $absentDays, true) ? null : '09:00',
            'check_out' => in_array($day, $absentDays, true) ? null : (isset($overtime[$day]) ? $overtime[$day] : '18:00'),
        ], (int) test()->userId);
    }
}

it('calculates salary with absence deduction and overtime', function (): void {
    // Shift-less employee: overtime needs a shift, so only absence applies here.
    fillSeptember($this->employee, [3, 4]);

    $run = app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09');
    $slip = Payslip::query()->where('payroll_run_id', $run->id)->firstOrFail();

    expect($slip->gross)->toBe('20000.00')
        ->and($slip->absent_days)->toBe('2.0')
        ->and($slip->present_days)->toBe(28)
        ->and($slip->absence_deduction)->toBe('1333.34')
        ->and($slip->net)->toBe('18666.66')
        ->and($run->fresh()->total_net)->toBe('18666.66')
        ->and($run->status)->toBe(PayrollStatus::Draft);
});

it('keeps typed deductions when the draft is regenerated', function (): void {
    fillSeptember($this->employee);
    $run = app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09');
    $slip = Payslip::query()->firstOrFail();

    $this->actingAs(makeUser(RoleName::Accountant))->put(route('admin.payslips.update', $slip), ['advance_deduction' => '2000.00', 'other_deduction' => '500.00'])->assertSessionHasNoErrors();

    app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09');

    expect($slip->fresh()->advance_deduction)->toBe('2000.00')
        ->and($slip->fresh()->net)->toBe('17500.00')
        ->and($run->fresh()->total_deductions)->toBe('2500.00');
});

it('locks a finalized payroll and then records salary payment', function (): void {
    fillSeptember($this->employee);
    $accountant = makeUser(RoleName::Accountant);
    $run = app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09');
    $slip = Payslip::query()->firstOrFail();

    $this->actingAs($accountant)->post(route('admin.payslips.pay', $slip), ['payment_method' => 'bkash'])->assertSessionHas('error');

    $this->actingAs($accountant)->post(route('admin.payroll.finalize', $run))->assertSessionHasNoErrors();

    expect(fn () => app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09'))->toThrow(DomainException::class)
        ->and(fn () => app(FinalizePayrollRunAction::class)->execute($run->fresh(), (int) test()->userId))->toThrow(DomainException::class);

    $this->actingAs($accountant)->put(route('admin.payslips.update', $slip), ['advance_deduction' => '100.00', 'other_deduction' => '0.00'])->assertSessionHas('error');

    $this->actingAs($accountant)->post(route('admin.payslips.pay', $slip), ['payment_method' => 'bkash', 'payment_reference' => 'TX123'])->assertSessionHasNoErrors();

    expect($slip->fresh()->paid_at)->not->toBeNull()
        ->and($run->fresh()->status)->toBe(PayrollStatus::Paid);

    $this->actingAs($accountant)->post(route('admin.payslips.pay', $slip), ['payment_method' => 'cash'])->assertSessionHas('error');
});

it('treats approved unpaid leave as absence', function (): void {
    fillSeptember($this->employee);
    AttendanceRecord::query()->whereDate('work_date', '2026-09-10')->update(['status' => 'leave', 'is_manual' => true]);
    LeaveRequest::query()->create([
        'branch_id' => $this->branch->id, 'employee_id' => $this->employee->id, 'type' => 'unpaid', 'from_date' => '2026-09-10', 'to_date' => '2026-09-10',
        'days' => 1, 'status' => 'approved',
    ]);

    app(GeneratePayrollRunAction::class)->execute($this->branch->id, '2026-09');

    expect(Payslip::query()->firstOrFail()->absent_days)->toBe('1.0');
});

it('hides salary data from roles without payroll access', function (): void {
    $manager = makeUser(RoleName::BranchManager);

    $this->actingAs($manager)->get(route('admin.employees.index'))
        ->assertInertia(fn ($page) => $page->where('canSeePay', false)->missing('employees.data.0.basic_salary'));
    $this->actingAs($manager)->get(route('admin.payroll.index'))->assertForbidden();

    $this->actingAs(makeUser(RoleName::Accountant))->get(route('admin.employees.index'))
        ->assertInertia(fn ($page) => $page->where('canSeePay', true)->where('employees.data.0.gross', '20000.00'));

    // A manager cannot change salary through the form.
    $this->actingAs($manager)->put(route('admin.employees.update', $this->employee), [
        'name' => 'Renamed', 'joined_on' => '2026-01-01', 'basic_salary' => '99999.00', 'is_active' => true,
    ])->assertSessionHasNoErrors();

    expect($this->employee->fresh()->basic_salary)->toBe('15000.00')->and($this->employee->fresh()->name)->toBe('Renamed');
});

it('generates payroll through the web form for the current branch', function (): void {
    fillSeptember($this->employee);

    $this->actingAs(makeUser(RoleName::Accountant, $this->branch))->post(route('admin.payroll.generate'), ['month' => '2026-09'])->assertRedirect();

    expect(PayrollRun::query()->where('month', '2026-09')->exists())->toBeTrue();
});
