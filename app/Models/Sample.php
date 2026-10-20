<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\SampleStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One sample container (BR01-260115-0042) holding one or more tests.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property int $patient_id
 * @property string $sample_code
 * @property string|null $sample_type
 * @property string|null $container
 * @property SampleStatus $status
 * @property int|null $collected_by
 * @property Carbon|null $collected_at
 * @property int|null $received_by
 * @property Carbon|null $received_at
 * @property string|null $reject_reason
 * @property int|null $recollection_of_id
 * @property-read Order $order
 * @property-read Patient $patient
 * @property-read Collection<int, OrderItem> $items
 */
class Sample extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'order_id', 'patient_id', 'sample_code', 'sample_type', 'container', 'status', 'recollection_of_id'];

    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'collected_at' => 'datetime',
            'received_at' => 'datetime',
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
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<Sample, $this>
     */
    public function recollectionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recollection_of_id');
    }

    /**
     * @return HasMany<Sample, $this>
     */
    public function recollections(): HasMany
    {
        return $this->hasMany(self::class, 'recollection_of_id');
    }

    /**
     * Samples waiting for collection at the counter.
     *
     * @param  Builder<Sample>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SampleStatus::Pending);
    }

    /**
     * Collected samples waiting to be received by the lab.
     *
     * @param  Builder<Sample>  $query
     */
    public function scopeAwaitingReceive(Builder $query): Builder
    {
        return $query->where('status', SampleStatus::Collected);
    }

    /**
     * Earliest expected report time across the tests in this sample.
     */
    public function expectedAt(): ?Carbon
    {
        return $this->items
            ->filter(fn (OrderItem $item): bool => $item->expected_at !== null && $item->status !== OrderItemStatus::Cancelled)
            ->map(fn (OrderItem $item): Carbon => $item->expected_at)
            ->min();
    }
}
