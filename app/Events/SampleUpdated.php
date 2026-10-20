<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sample;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The lab worklist refreshes when a sample is collected, received,
 * rejected or recollected. Payload carries IDs only.
 */
class SampleUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Sample $sample) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $t = $this->sample->tenant_id;

        return [
            new PrivateChannel("tenant.{$t}.branch.{$this->sample->branch_id}.lab"),
            new PrivateChannel("tenant.{$t}.branch.{$this->sample->branch_id}.reception"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SampleUpdated';
    }

    /**
     * @return array{sample_id: int, status: string}
     */
    public function broadcastWith(): array
    {
        return ['sample_id' => $this->sample->id, 'status' => $this->sample->status->value];
    }
}
