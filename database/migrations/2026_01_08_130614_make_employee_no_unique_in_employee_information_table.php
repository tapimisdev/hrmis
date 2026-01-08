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
        Schema::table('employee_information', function (Blueprint $table) {
            $table->unique('employee_no');
            $table->unique('biometrics_id');
        });

        Schema::table('employee_personal', function (Blueprint $table) {
            $table->unique('employee_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->dropUnique(['employee_no']);
            $table->dropUnique(['biometrics_id']);
        });

        Schema::table('employee_personal', function (Blueprint $table) {
            $table->dropUnique(['employee_no']);
        });
    }

};
