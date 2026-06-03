<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('violation_sanction_settings') && ! Schema::hasTable('violation_settings')) {
            Schema::rename('violation_sanction_settings', 'violation_settings');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('violation_settings') && ! Schema::hasTable('violation_sanction_settings')) {
            Schema::rename('violation_settings', 'violation_sanction_settings');
        }
    }
};
