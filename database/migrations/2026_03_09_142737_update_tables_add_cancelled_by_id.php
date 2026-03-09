<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'leave_applications',
            'offset_applications',
            'obs_applications',
            'special_order_applications',
            'lto_applications',
            'overtime_applications'
        ];

        foreach ($tables as $tableName) {

            // Rename approver_id -> actioned_by
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('approver_id', 'actioned_by');
            });

            // Add cancelled_by after actioned_by
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('cancelled_by')
                    ->nullable()
                    ->after('actioned_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'leave_applications',
            'offset_applications',
            'obs_applications',
            'special_order_applications',
            'lto_applications',
            'overtime_applications'
        ];

        foreach ($tables as $tableName) {

            // Remove cancelled_by
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('cancelled_by');
            });

            // Rename actioned_by -> approver_id
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('actioned_by', 'approver_id');
            });
        }
    }
};