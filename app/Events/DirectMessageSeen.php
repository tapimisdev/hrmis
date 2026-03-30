<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSeen implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('direct-messages.' . $this->payload['reader_id']),
            new PrivateChannel('direct-messages.' . $this->payload['partner_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct-message.seen';
    }

    public function broadcastWith(): array
    {
        return [
            'payload' => $this->payload,
        ];
    }
}
