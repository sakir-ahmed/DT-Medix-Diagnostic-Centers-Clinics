<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\PayrollStatus;
use App\Exceptions\DomainException;
use App\Models\PayrollRun;

final class FinalizePayrollRunAction
{
    public function execute(PayrollRun $run, int $userId): PayrollRun
    {
        if ($run->status !== PayrollStatus::Draft) {
            throw DomainException::make('PAYROLL_LOCKED');
        }

        $run->forceFill(['status' => PayrollStatus::Finalized, 'finalized_by' => $userId, 'finalized_at' => now()])->save();
        activity()->performedOn($run)->withProperties(['month' => $run->month, 'net' => $run->total_net])->log('payroll finalized');

        return $run;
    }
}
