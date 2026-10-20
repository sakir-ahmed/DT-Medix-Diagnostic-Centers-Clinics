<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One test on an order with a price snapshot. net_amount is the price after
 * package pricing and the invoice discount allocation.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property int $test_id
 * @property int|null $package_id
 * @property int|null $department_id
 * @property int|null $sample_id
 * @property string $code
 * @property string $name
 * @property string $price
 * @property string $net_amount
 * @property bool $vat_applicable
 * @property int $tat_hours
 * @property Carbon|null $expected_at
 * @property OrderItemStatus $status
 * @property Carbon|null $cancelled_at
 * @property string|null $cancel_reason
 * @property-read Order $order
 * @property-read Test $test
 * @property-read Sample|null $sample
 * @property-read Collection<int, TestResult> $results
 * @property-read Collection<int, ResultAttachment> $attachments
 * @property-read NarrativeResult|null $narrative
 */
class OrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = ['order_id', 'test_id', 'package_id', 'department_id', 'sample_id', 'code', 'name', 'price', 'net_amount', 'vat_applicable', 'tat_hours', 'expected_at', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'vat_applicable' => 'boolean',
            'tat_hours' => 'integer',
            'expected_at' => 'datetime',
            'status' => OrderItemStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Test, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Sample, $this>
     */
    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    /**
     * @return HasMany<TestResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class, 'order_item_id');
    }

    /**
     * @return HasOne<NarrativeResult, $this>
     */
    public function narrative(): HasOne
    {
        return $this->hasOne(NarrativeResult::class, 'order_item_id');
    }

    /**
     * @return HasMany<ResultAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ResultAttachment::class, 'order_item_id');
    }

    /**
     * A critical result that still needs its acknowledgement.
     */
    public function hasUnacknowledgedCritical(): bool
    {
        return $this->results()->where('flag', 'C')->whereNull('critical_ack_at')->exists();
    }
}
