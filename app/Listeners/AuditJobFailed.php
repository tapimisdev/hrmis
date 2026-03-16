<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;

class AuditJobFailed
{
    public function handle(JobFailed $event)
    {
        $payload = $event->job->payload();

        DB::table('trails')->insert([
            'actioned_by_id' => $command->userId ?? null,
            'actioned_by_name' => $command->name ?? 'System',
            'method' => 'POST',
            'controller' => $payload['displayName'] ?? 'Job',
            'description' => $event->exception->getMessage(),
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}