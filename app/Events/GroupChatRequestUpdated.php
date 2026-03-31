<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupChatRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public array $request,
        public array $recipientIds
    ) {
    }

    public function broadcastOn(): array
    {
        return collect($this->recipientIds)
            ->unique()
            ->map(fn ($userId) => new PrivateChannel('direct-messages.' . $userId))
            ->values()
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'group-chat.request-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'request' => $this->request,
        ];
    }
}
