<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\PayrollStatus;
use App\Exceptions\DomainException;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;

final class MarkPayslipPaidAction
{
    public function execute(Payslip $payslip, string $method, ?string $reference): Payslip
    {
        $run = $payslip->run()->withoutGlobalScopes()->firstOrFail();

        if ($run->status === PayrollStatus::Draft) {
            throw DomainException::make('PAYROLL_NOT_FINALIZED');
        }

        if ($payslip->paid_at !== null) {
            throw DomainException::make('PAYSLIP_ALREADY_PAID');
        }

        return DB::transaction(function () use ($payslip, $method, $reference, $run): Payslip {
            $payslip->forceFill(['paid_at' => now(), 'payment_method' => $method, 'payment_reference' => $reference])->save();

            $unpaid = Payslip::query()->withoutGlobalScopes()->where('payroll_run_id', $run->id)->whereNull('paid_at')->exists();

            if (! $unpaid) {
                $run->forceFill(['status' => PayrollStatus::Paid])->save();
            }

            activity()->performedOn($payslip)->withProperties(['method' => $method])->log('payslip paid');

            return $payslip;
        });
    }
}
