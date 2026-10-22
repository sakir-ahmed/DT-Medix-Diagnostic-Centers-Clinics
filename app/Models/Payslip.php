<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A monthly salary snapshot. Employee data is copied so later changes never
 * alter an issued payslip.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $payroll_run_id
 * @property int $employee_id
 * @property string $net
 * @property Carbon|null $paid_at
 */
class Payslip extends Model
{
    use BelongsToBranch;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'absent_days' => 'decimal:1',
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'gross' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'late_deduction' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
