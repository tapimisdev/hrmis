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
        Schema::create('payroll_salary_permanent_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_salary_id')->constrained('payroll_salary');
            $table->string('employee_no');
            $table->string('name');
            $table->string('position');
            $table->decimal('monthly_rate', 12, 2);
            $table->string('salary_grade');

            $table->decimal('ut', 12, 2);
            $table->decimal('absences', 12, 2);
            $table->decimal('overtime', 12, 2);
            $table->decimal('holiday', 12, 2);

            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->decimal('salary_adjustment', 12, 2);
            
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_permanent_employees');
    }
};
