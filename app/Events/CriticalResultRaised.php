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
 * A submitted result contains a critical value. Payload carries IDs only.
 */
class CriticalResultRaised implements ShouldBroadcast
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
            new PrivateChannel("branch.{$branchId}.alerts"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CriticalResultRaised';
    }

    /**
     * @return array{item_id: int, sample_id: int|null}
     */
    public function broadcastWith(): array
    {
        return ['item_id' => $this->item->id, 'sample_id' => $this->item->sample_id];
    }
}
