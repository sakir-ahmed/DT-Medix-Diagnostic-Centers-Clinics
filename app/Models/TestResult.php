<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One parameter result with the value, unit, reference range and flag used
 * at entry time, so later catalog changes never alter old reports.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_item_id
 * @property int $test_parameter_id
 * @property int $patient_id
 * @property string $code
 * @property string $name
 * @property string|null $unit
 * @property string $result_type
 * @property string|null $value
 * @property string|null $flag
 * @property string|null $range_low
 * @property string|null $range_high
 * @property string|null $range_critical_low
 * @property string|null $range_critical_high
 * @property string|null $range_text
 * @property string|null $remarks
 * @property string|null $critical_informed_name
 * @property Carbon|null $critical_informed_at
 * @property int|null $critical_ack_by
 * @property Carbon|null $critical_ack_at
 * @property int|null $entered_by
 * @property-read OrderItem $item
 * @property-read TestParameter $parameter
 * @property-read Patient $patient
 */
class TestResult extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'order_item_id', 'test_parameter_id', 'patient_id', 'code', 'name', 'unit', 'result_type', 'value', 'flag',
        'range_low', 'range_high', 'range_critical_low', 'range_critical_high', 'range_text', 'remarks',
        'critical_informed_name', 'critical_informed_at', 'critical_ack_by', 'critical_ack_at', 'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'critical_informed_at' => 'datetime',
            'critical_ack_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<TestParameter, $this>
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'test_parameter_id');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isCritical(): bool
    {
        return $this->flag === 'C';
    }

    public function isAcknowledged(): bool
    {
        return $this->critical_ack_at !== null;
    }
}
