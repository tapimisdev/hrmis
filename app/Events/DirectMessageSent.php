<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $message
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('direct-messages.' . $this->message['sender_id']),
            new PrivateChannel('direct-messages.' . $this->message['recipient_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct-message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
