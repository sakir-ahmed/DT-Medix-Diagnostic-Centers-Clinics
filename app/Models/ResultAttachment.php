<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An image or PDF attached to a narrative result, stored on the private disk.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_item_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size
 * @property int|null $uploaded_by
 * @property-read OrderItem $item
 */
class ResultAttachment extends Model
{
    use BelongsToTenant;

    protected $fillable = ['order_item_id', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
