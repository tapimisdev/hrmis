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

        foreach ([
            'Tardiness / Late',
            'Undertime',
            'Unauthorized Absence',
        ] as $violationType) {
            DB::table('violation_settings')
                ->where('violation_type', $violationType)
                ->update([
                    'action_name' => $violationType,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Base attendance violations should not imply habitual or frequent classification.
    }
};
