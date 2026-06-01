<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimelogMonitoringUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $date,
        public readonly int $userId,
        public readonly string $type,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('timelogs.monitoring');
    }

    public function broadcastAs(): string
    {
        return 'TimelogMonitoringUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'date' => $this->date,
            'user_id' => $this->userId,
            'type' => $this->type,
            'timestamp' => now()->toISOString(),
        ];
    }
}
