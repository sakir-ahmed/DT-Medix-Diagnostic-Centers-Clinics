<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Reception and the doctor's queue refresh. Payload carries IDs only.
 */
class AppointmentUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Appointment $appointment) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $t = $this->appointment->tenant_id;

        return [
            new PrivateChannel("tenant.{$t}.branch.{$this->appointment->branch_id}.reception"),
            new PrivateChannel("tenant.{$t}.doctor.{$this->appointment->doctor_id}.queue"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AppointmentUpdated';
    }

    /**
     * @return array{appointment_id: int, status: string}
     */
    public function broadcastWith(): array
    {
        return ['appointment_id' => $this->appointment->id, 'status' => $this->appointment->status->value];
    }
}
