<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A refund with a reason; money leaves only after approval.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $branch_id
 * @property int $invoice_id
 * @property string $amount
 * @property string $method
 * @property string $reason
 * @property ApprovalStatus $status
 * @property int|null $requested_by
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property Carbon $created_at
 * @property-read Invoice $invoice
 * @property-read User|null $requester
 */
class Refund extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    protected $fillable = ['branch_id', 'invoice_id', 'amount', 'method', 'reason', 'status', 'requested_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'status' => ApprovalStatus::class, 'decided_at' => 'datetime'];
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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
