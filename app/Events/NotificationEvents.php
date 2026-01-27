<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvents implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $id;
    public $type;
    public $sender;
    public $receiver; // employees | admins | user_id
    public $data;

    protected $channel;
    protected $isPrivate = false;

    public function __construct(
        string $type,
        string $sender,
        string|int $receiver,
        array $data
    ) {
        $notification = Notification::create([
            'type' => $type,
            'sender' => $sender,
            'receiver' => (string) $receiver,
            'data' => $data,
        ]);

        $this->id = $notification->id;
        $this->type = $type;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->data = $data;

        /*
        |--------------------------------------------------------------------------
        | Channel Resolution (receiver-driven)
        |--------------------------------------------------------------------------
        */

        if ($receiver === 'employees') {
            $this->channel = 'employees.notifications';
            $this->isPrivate = false;

        } elseif ($receiver === 'admins') {
            $this->channel = 'admins.notifications';
            $this->isPrivate = false;

        } elseif (is_numeric($receiver)) {
            $this->channel = 'user.notifications.' . $receiver;
            $this->isPrivate = true;

        } else {
            throw new \InvalidArgumentException('Invalid notification receiver');
        }
    }

    public function broadcastOn()
    {
        return $this->isPrivate
            ? new PrivateChannel($this->channel)
            : new Channel($this->channel);
    }

    public function broadcastAs(): string
    {
        return 'notification-event';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'data' => $this->data,
        ];
    }
}
