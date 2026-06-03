<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings') || ! Schema::hasColumn('violation_settings', 'evaluation_period')) {
            return;
        }

        DB::table('violation_settings')
            ->where('evaluation_period', 'Jan-Jun or Jul-Dec')
            ->update([
                'evaluation_period' => 'Jan–Jun or Jul–Dec',
                'updated_at' => now(),
            ]);

        DB::table('violation_settings')
            ->where('evaluation_period', 'Jan-Dec')
            ->update([
                'evaluation_period' => 'Jan–Dec',
                'updated_at' => now(),
            ]);

        DB::table('violation_settings')
            ->where('evaluation_period', 'Every FC day')
            ->update([
                'evaluation_period' => 'Count monthly',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('violation_settings') || ! Schema::hasColumn('violation_settings', 'evaluation_period')) {
            return;
        }

        DB::table('violation_settings')
            ->where('evaluation_period', 'Jan–Jun or Jul–Dec')
            ->update([
                'evaluation_period' => 'Jan-Jun or Jul-Dec',
                'updated_at' => now(),
            ]);

        DB::table('violation_settings')
            ->where('evaluation_period', 'Jan–Dec')
            ->update([
                'evaluation_period' => 'Jan-Dec',
                'updated_at' => now(),
            ]);
    }
};
