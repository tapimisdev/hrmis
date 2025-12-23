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
            $table->decimal('ewt_2', 15, 2)->default(0)->nullable()->after('pagibig');
            $table->decimal('percentage_tax_3', 15, 2)->default(0)->nullable()->after('ewt_2');
            $table->decimal('tax_ewt_5', 15, 2)->default(0)->nullable()->after('percentage_tax_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_salary_employee', function (Blueprint $table) {
            $table->dropColumn(['ewt_2', 'percentage_tax_3', 'tax_ewt_5']);
        });
    }
};
