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
        Schema::create('payroll_salary_permanents_employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pspe_id')->constrained('payroll_salary_permanent_employees');
            $table->string('deduction_type');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_permanents_employee_deductions');
    }
};
