<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\User; // Add this import
use Illuminate\Broadcasting\Channel;
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
    public $receiver;
    public $data;
    public $channel; // Add this to store the channel name

    public function __construct(string $type, string $sender, string $receiver, array $data)
    {
        // Save to database
        $notification = Notification::create([
            'type' => $type,
            'sender' => $sender,
            'receiver' => $receiver,
            'data' => $data,
        ]);

        $this->id = $notification->id;
        $this->type = $notification->type;
        $this->sender = $notification->sender;
        $this->receiver = $notification->receiver;
        $this->data = $notification->data;

        // Determine the channel based on receiver's role
        // Assuming $receiver is the user ID (adjust if it's something else like username)

        if($this->receiver == '*') {
            $this->channel = 'employee-channel';
        }
        
        if(!in_array($this->receiver, ['admin', 'employee'])) {
            $user = User::find($this->receiver);
            if ($user) {
                $adminRoles = ['hr_manager', 'hr_clerk', 'hr_admin', 'super_admin'];
                $hasAdminRole = $user->roles->pluck('name')->intersect($adminRoles)->isNotEmpty();
                $this->channel = $hasAdminRole ? 'admin-channel' : 'employee-channel';
            }
        } else {
            $this->channel = $this->receiver . '-channel';
        }
        
        
    }

    public function broadcastOn(): Channel
    {
        return new Channel($this->channel);
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