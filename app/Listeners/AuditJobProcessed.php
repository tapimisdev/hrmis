<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;

class AuditJobProcessed
{
    public function handle(JobProcessed $event)
    {
        $payload = $event->job->payload();

        $command = unserialize($payload['data']['command']);

        DB::table('trails')->insert([
            'actioned_by_id' => $command->userId ?? null,
            'actioned_by_name' => $command->name ?? 'System',
            'method' => 'POST',
            'controller' => $payload['displayName'] ?? 'Job',
            'description' => '',
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}