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
            ->whereRaw("LOWER(violation_type) LIKE '%late%'")
            ->orWhereRaw("LOWER(violation_type) LIKE '%under%'")
            ->update(['violation_type' => 'UT']);

        DB::table('violation_settings')
            ->whereRaw("LOWER(violation_type) LIKE '%absen%'")
            ->update(['violation_type' => 'Absences']);

        DB::table('violation_settings')
            ->whereRaw("LOWER(violation_type) LIKE '%timelog%'")
            ->orWhereRaw("LOWER(violation_type) LIKE '%incomplete%'")
            ->orWhereRaw("LOWER(violation_type) LIKE '%incorrect%'")
            ->orWhereRaw("LOWER(violation_type) LIKE '%discrepancy%'")
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
