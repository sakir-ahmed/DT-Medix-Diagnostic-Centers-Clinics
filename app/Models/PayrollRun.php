<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $month
 * @property PayrollStatus $status
 * @property string $total_gross
 * @property string $total_deductions
 * @property string $total_net
 */
class PayrollRun extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'month', 'status', 'total_gross', 'total_deductions', 'total_net', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['status' => PayrollStatus::class, 'finalized_at' => 'datetime', 'total_gross' => 'decimal:2', 'total_deductions' => 'decimal:2', 'total_net' => 'decimal:2'];
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
