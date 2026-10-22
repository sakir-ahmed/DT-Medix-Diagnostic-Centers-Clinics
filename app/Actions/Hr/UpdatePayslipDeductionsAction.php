<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\PayrollStatus;
use App\Exceptions\DomainException;
use App\Models\Payslip;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

final class UpdatePayslipDeductionsAction
{
    public function execute(Payslip $payslip, string $advance, string $other): Payslip
    {
        $run = $payslip->run()->withoutGlobalScopes()->firstOrFail();

        if ($run->status !== PayrollStatus::Draft) {
            throw DomainException::make('PAYROLL_LOCKED');
        }

        return DB::transaction(function () use ($payslip, $advance, $other, $run): Payslip {
            $deductions = Money::add($payslip->absence_deduction, $payslip->late_deduction, $advance, $other);
            $payslip->forceFill([
                'advance_deduction' => Money::of($advance),
                'other_deduction' => Money::of($other),
                'net' => Money::max('0.00', Money::sub($payslip->gross, $deductions)),
            ])->save();

            $slips = Payslip::query()->withoutGlobalScopes()->where('payroll_run_id', $run->id)->get();
            $run->forceFill([
                'total_deductions' => $slips->reduce(fn (string $carry, Payslip $slip): string => Money::add($carry, $slip->absence_deduction, $slip->late_deduction, $slip->advance_deduction, $slip->other_deduction), '0.00'),
                'total_net' => Money::add('0.00', ...$slips->pluck('net')->all()),
            ])->save();

            return $payslip;
        });
    }
}
