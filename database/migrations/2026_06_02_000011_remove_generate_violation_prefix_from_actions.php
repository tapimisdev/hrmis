<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings') || ! Schema::hasColumn('violation_settings', 'action_name')) {
            return;
        }

        DB::table('violation_settings')
            ->where('action_name', 'like', 'Generate violation:%')
            ->update([
                'action_name' => DB::raw("TRIM(REPLACE(action_name, 'Generate violation:', ''))"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings') || ! Schema::hasColumn('violation_settings', 'action_name')) {
            return;
        }

        foreach (['Habitual Tardiness', 'Frequent Undertime', 'Habitual Absenteeism'] as $actionName) {
            DB::table('violation_settings')
                ->where('action_name', $actionName)
                ->update([
                    'action_name' => 'Generate violation: ' . $actionName,
                    'updated_at' => now(),
                ]);
        }
    }
};
