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
        Schema::create('payroll_salary_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_salary_id')->constrained('payroll_salary');
            $table->string('employee_no');
            $table->string('name');
            $table->string('position');
            $table->string('salary_grade');

            $table->decimal('ut', 12, 2);
            $table->decimal('absences', 12, 2);
            $table->decimal('overtime', 12, 2);

            // mandatory deductions
            $table->decimal('gsis', 12, 2);
            $table->decimal('philhealth', 12, 2);
            $table->decimal('pagibig', 12, 2);

            $table->decimal('w_tax', 12, 2);

            $table->decimal('total_deductions', 12, 2);
            $table->decimal('total_earnings', 12, 2);

            $table->decimal('monthly_rate', 12, 2);
            $table->decimal('basic_pay', 12, 2);
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('net_pay', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_employee');
    }
};
