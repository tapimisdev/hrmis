<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('n_taxation_employees', function (Blueprint $table) {
            $table->decimal('salary', 12, 2)->nullable()->after('employee_no');
            $table->decimal('hazard_pay', 12, 2)->nullable()->after('salary');
            $table->decimal('longevity', 12, 2)->nullable()->after('hazard_pay');
            $table->unique(['n_taxation_id', 'employee_no'], 'n_taxation_employees_year_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::table('n_taxation_employees', function (Blueprint $table) {
            $table->dropUnique('n_taxation_employees_year_employee_unique');
            $table->dropColumn(['salary', 'hazard_pay', 'longevity']);
        });
    }
};
