<?php

namespace App\Events;

use App\Http\Controllers\Admin\Channels\OnlineUsersController;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $user,
        public readonly string $status,
        public readonly ?string $timestamp = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('online-users'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'online-users.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user' => $this->user,
            'status' => $this->status,
            'timestamp' => $this->timestamp ?? now()->toISOString(),
        ];
    }
}
