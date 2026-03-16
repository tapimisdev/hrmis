<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Services\EventService;
use App\Enums\EmploymentTypesEnum;

class LogNewLogin
{
    /**
     * Create the event listener.
     */

    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event)
    {
        $user = $event->user;
        $sessionId = session()->getId();

        $otherSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $sessionId)
            ->count();

        if ($otherSessions > 0) {
            $employeeTypes = array_map(fn($case) => $case->value, EmploymentTypesEnum::cases());
            $employmentValue = $user->employment_type_id?->value ?? $user->employment_type_id;
            $link = in_array($employmentValue, $employeeTypes) ? '/employee/profile' : '';
            
            $payload = [
                'type' => 'system',
                'sender' => $user->id,
                'receiver' => $user->id,
                'message'  => '%b[SYSTEM]%b: A new login was made.',
                'link'     => $link,
            ];
            
            $this->eventService->pushNotification($payload);
        }
    }
}
