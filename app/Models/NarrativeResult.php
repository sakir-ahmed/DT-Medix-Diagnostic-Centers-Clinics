<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Findings and impression for narrative tests (imaging, cardiology).
 *
 * @property int $id
 * @property int $order_item_id
 * @property string|null $findings
 * @property string|null $impression
 * @property-read OrderItem $item
 */
class NarrativeResult extends Model
{
    protected $fillable = ['order_item_id', 'findings', 'impression'];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
