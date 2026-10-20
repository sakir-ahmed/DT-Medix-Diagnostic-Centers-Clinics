<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Money columns are recalculated by InvoiceCalculator; due is never typed.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property int $patient_id
 * @property string $invoice_no
 * @property string $subtotal
 * @property string|null $discount_type
 * @property string $discount_value
 * @property string $discount_amount
 * @property string|null $discount_reason
 * @property int|null $discount_approved_by
 * @property string $vat_amount
 * @property string $service_charge
 * @property string $total
 * @property string $paid
 * @property string $refunded
 * @property string $due
 * @property InvoiceStatus $status
 * @property Carbon|null $fully_paid_at
 * @property Carbon $created_at
 * @property-read Order $order
 * @property-read Patient $patient
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Refund> $refunds
 */
class Invoice extends Model
{
    use BelongsToBranch;
    use LogsActivity;

    protected $fillable = ['branch_id', 'order_id', 'patient_id', 'invoice_no', 'discount_type', 'discount_value', 'discount_reason'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'refunded' => 'decimal:2',
            'due' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'fully_paid_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['discount_type', 'discount_value', 'discount_amount', 'total', 'status'])->logOnlyDirty();
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
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->orderBy('id');
    }

    /**
     * @return HasMany<DiscountRequest, $this>
     */
    public function discountRequests(): HasMany
    {
        return $this->hasMany(DiscountRequest::class)->orderBy('id');
    }
}
