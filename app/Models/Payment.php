<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A money receipt for one payment line.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $invoice_id
 * @property int $patient_id
 * @property string $receipt_no
 * @property PaymentMethod $method
 * @property string $amount
 * @property string|null $reference
 * @property string $status
 * @property int|null $received_by
 * @property Carbon $paid_at
 * @property-read Invoice $invoice
 * @property-read User|null $receiver
 */
class Payment extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'invoice_id', 'patient_id', 'receipt_no', 'method', 'amount', 'reference', 'status', 'received_by', 'paid_at'];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
