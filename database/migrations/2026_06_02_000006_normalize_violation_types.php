<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('violation_settings')) {
            return;
        }

        DB::table('violation_settings')
            ->whereIn('violation_type', ['Lates', 'Undertime', 'Late / Undertime'])
            ->update(['violation_type' => 'UT']);

        DB::table('violation_settings')
            ->whereIn('violation_type', ['Incorrect Timelogs', 'Incomplete Log', 'Incomplete Logs'])
            ->update(['violation_type' => 'Incomplete Timelogs']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
