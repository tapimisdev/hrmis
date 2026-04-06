<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectConversationInfoUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return collect($this->payload['participants'] ?? [])
            ->map(fn ($userId) => new PrivateChannel('direct-messages.' . (int) $userId))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'direct-conversation.info-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'payload' => $this->payload,
        ];
    }
}
