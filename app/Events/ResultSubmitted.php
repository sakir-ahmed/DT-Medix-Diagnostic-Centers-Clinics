<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Results were submitted for verification. Payload carries IDs only.
 */
class ResultSubmitted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly OrderItem $item) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $branchId = data_get($this->item, 'sample.branch_id', $this->item->order->branch_id);

        return [
            new PrivateChannel("branch.{$branchId}.lab"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ResultSubmitted';
    }

    /**
     * @return array{item_id: int, sample_id: int|null, status: string}
     */
    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->item->id,
            'sample_id' => $this->item->sample_id,
            'status' => $this->item->status->value,
        ];
    }
}
