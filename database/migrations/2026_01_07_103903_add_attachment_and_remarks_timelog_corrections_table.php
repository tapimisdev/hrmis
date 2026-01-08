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
        Schema::table('timelog_corrections', function (Blueprint $table) {
            $table->date('date')->after('id');
            $table->string('attachment')->nullable()->after('work_schedule_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('is_approved');
            $table->text('remarks')->nullable()->after('attachment');
        });

        Schema::table('timelog_corrections', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timelog_corrections', function (Blueprint $table) {
            // Restore old column first
            $table->boolean('is_approved')->default(false);
        });

        Schema::table('timelog_corrections', function (Blueprint $table) {
            // Now drop the new columns safely
            $table->dropColumn(['date', 'attachment', 'status', 'remarks']);
        });
    }
};
