<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        $message = $this->payload['message'] ?? [];

        return [
            new PrivateChannel('direct-messages.' . ($message['sender_id'] ?? 0)),
            new PrivateChannel('direct-messages.' . ($message['recipient_id'] ?? 0)),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct-message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'payload' => $this->payload,
        ];
    }
}
