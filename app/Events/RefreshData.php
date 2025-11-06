<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshData implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): Channel
    {
        Log::info('RefreshData broadcastOn() called - returning refresh');
        return new Channel('refresh');
    }

    public function broadcastAs()
    {
        Log::info('RefreshData broadcastAs() called - returning RefreshData');
        return 'RefreshData';
    }
    
    public function broadcastWith()
    {
        Log::info('RefreshData broadcastWith() called');
        return [
            'message' => 'Refresh Received',
            'timestamp' => now()->toISOString(),
        ];
    }
}