<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A test order for one patient visit. Billed by exactly one invoice.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $branch_id
 * @property int $patient_id
 * @property int|null $appointment_id
 * @property int|null $referrer_id
 * @property string $urgency
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property-read Patient $patient
 * @property-read Invoice|null $invoice
 * @property-read Referrer|null $referrer
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, Sample> $samples
 */
class Order extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    protected $fillable = ['branch_id', 'patient_id', 'appointment_id', 'referrer_id', 'urgency', 'status', 'notes', 'created_by'];

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Referrer, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<Sample, $this>
     */
    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class)->orderBy('id');
    }
}
