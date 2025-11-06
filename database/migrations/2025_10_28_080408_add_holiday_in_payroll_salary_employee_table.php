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
        Schema::table('payroll_salary_employee', function (Blueprint $table) {
            $table->decimal('holiday', 12, 2)->after('overtime');
            $table->decimal('salary_adjustment', 12, 2)->after('net_pay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_salary_employee', function (Blueprint $table) {
            $table->dropColumn('holiday');
            $table->dropColumn('salary_adjustment');
        });
    }
};
