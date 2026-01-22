<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvents implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $id;
    public $sender;
    public $receiver;
    public $data;

    public function __construct(string $sender, string $receiver, array $data)
    {
        // Save to database
        $notification = Notification::create([
            'sender' => $sender,
            'receiver' => $receiver,
            'data' => $data,
        ]);

        $this->id = $notification->id;
        $this->sender = $notification->sender;
        $this->receiver = $notification->receiver;
        $this->data = $notification->data;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('public-channel');
    }

    public function broadcastAs(): string
    {
        return 'public-channel-event';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'data' => $this->data,
        ];
    }
}
