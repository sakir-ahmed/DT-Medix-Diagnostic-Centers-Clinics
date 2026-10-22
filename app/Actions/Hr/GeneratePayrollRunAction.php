<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PayrollStatus;
use App\Exceptions\DomainException;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Builds or refreshes the draft payroll of one branch and month from
 * attendance. Advance and other deductions typed on a draft payslip are kept
 * when the run is regenerated. A finalized run is immutable.
 */
final class GeneratePayrollRunAction
{
    public function __construct(private readonly RecalculateAttendanceAction $recalculate) {}

    public function execute(int $branchId, string $month): PayrollRun
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->endOfMonth()->startOfDay();

        $run = PayrollRun::query()->withoutGlobalScopes()->where('branch_id', $branchId)->where('month', $month)->first();

        if ($run !== null && $run->status !== PayrollStatus::Draft) {
            throw DomainException::make('PAYROLL_LOCKED');
        }

        return DB::transaction(function () use ($branchId, $month, $start, $end, $run): PayrollRun {
            $run ??= PayrollRun::query()->withoutGlobalScopes()->create(['branch_id' => $branchId, 'month' => $month, 'status' => PayrollStatus::Draft]);

            $employees = Employee::query()->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereDate('joined_on', '<=', $end)
                ->with('workShift')
                ->get();

            foreach ($employees as $employee) {
                $this->recalculate->execute($employee, $start, $end->isFuture() ? CarbonImmutable::today() : $end);
                $this->payslip($run, $employee, $start, $end);
            }

            Payslip::query()->withoutGlobalScopes()->where('payroll_run_id', $run->id)->whereNotIn('employee_id', $employees->pluck('id'))->delete();

            $slips = Payslip::query()->withoutGlobalScopes()->where('payroll_run_id', $run->id)->get();
            $deductions = $slips->reduce(fn (string $carry, Payslip $slip): string => Money::add($carry, $slip->absence_deduction, $slip->late_deduction, $slip->advance_deduction, $slip->other_deduction), '0.00');

            $run->forceFill([
                'total_gross' => Money::add('0.00', ...$slips->pluck('gross')->all()),
                'total_deductions' => $deductions,
                'total_net' => Money::add('0.00', ...$slips->pluck('net')->all()),
            ])->save();

            return $run;
        });
    }

    private function payslip(PayrollRun $run, Employee $employee, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $monthDays = (int) $start->daysInMonth;
        $records = AttendanceRecord::query()->withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $unpaidDays = $this->unpaidLeaveDates($employee, $start, $end);
        $absent = 0.0;
        $present = 0;
        $late = 0;
        $paidLeave = 0;

        foreach ($records as $record) {
            $day = $record->work_date->toDateString();

            match (true) {
                $record->status === AttendanceStatus::Absent => $absent += 1,
                $record->status === AttendanceStatus::HalfDay => [$absent += 0.5, $present++],
                $record->status === AttendanceStatus::Leave && isset($unpaidDays[$day]) => $absent += 1,
                $record->status === AttendanceStatus::Leave => $paidLeave++,
                in_array($record->status, [AttendanceStatus::Present, AttendanceStatus::Late], true) => $present++,
                default => null,
            };

            if ($record->status === AttendanceStatus::Late) {
                $late++;
            }
        }

        $beforeJoining = CarbonImmutable::parse($employee->joined_on)->startOfDay()->greaterThan($start)
            ? (int) $start->diffInDays(CarbonImmutable::parse($employee->joined_on)->startOfDay())
            : 0;
        $absent += $beforeJoining;

        $overtime = (int) $records->sum('overtime_minutes');
        $allowances = Money::add($employee->house_allowance, $employee->medical_allowance, $employee->transport_allowance, $employee->other_allowance);
        $fixed = Money::add($employee->basic_salary, $allowances);
        $daily = Money::of(bcdiv($fixed, (string) $monthDays, 6));
        $overtimePay = Money::round(Money::mul($employee->overtime_rate, bcdiv((string) $overtime, '60', 6)));
        $absenceDeduction = Money::round(Money::mul($daily, number_format($absent, 1, '.', '')));

        $perDeduction = (int) setting('hr_late_days_per_deduction', 3);
        $lateDeduction = $perDeduction > 0 ? Money::round(Money::mul($daily, intdiv($late, $perDeduction))) : '0.00';

        $existing = Payslip::query()->withoutGlobalScopes()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->first();
        $advance = $existing->advance_deduction ?? '0.00';
        $other = $existing->other_deduction ?? '0.00';

        $gross = Money::add($fixed, $overtimePay);
        $net = Money::max('0.00', Money::sub($gross, Money::add($absenceDeduction, $lateDeduction, $advance, $other)));

        Payslip::query()->withoutGlobalScopes()->updateOrCreate(
            ['payroll_run_id' => $run->id, 'employee_id' => $employee->id],
            [
                'branch_id' => $run->branch_id,
                'employee_code' => $employee->code,
                'employee_name' => $employee->name,
                'designation' => $employee->designation,
                'month_days' => $monthDays,
                'present_days' => $present,
                'late_count' => $late,
                'paid_leave_days' => $paidLeave,
                'absent_days' => $absent,
                'overtime_minutes' => $overtime,
                'basic_salary' => $employee->basic_salary,
                'allowances' => $allowances,
                'overtime_pay' => $overtimePay,
                'gross' => $gross,
                'absence_deduction' => $absenceDeduction,
                'late_deduction' => $lateDeduction,
                'advance_deduction' => $advance,
                'other_deduction' => $other,
                'net' => $net,
            ],
        );
    }

    /**
     * @return array<string, true>
     */
    private function unpaidLeaveDates(Employee $employee, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];

        $leaves = LeaveRequest::query()->withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveStatus::Approved)
            ->where('type', LeaveType::Unpaid)
            ->whereDate('from_date', '<=', $end)
            ->whereDate('to_date', '>=', $start)
            ->get();

        foreach ($leaves as $leave) {
            for ($day = CarbonImmutable::parse($leave->from_date); $day->lessThanOrEqualTo($leave->to_date); $day = $day->addDay()) {
                $dates[$day->toDateString()] = true;
            }
        }

        return $dates;
    }
}
