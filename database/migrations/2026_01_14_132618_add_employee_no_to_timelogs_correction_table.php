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
            $table->string('employee_no')->after('id');
            $table->string('reference_no')->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timelog_corrections', function (Blueprint $table) {
            $table->dropColumn(['employee_no', 'reference_no', 'status', 'remarks']);
        });
    }
};
