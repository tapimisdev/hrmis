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
        Schema::create('payroll_salary_employee_edeductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_se_id')->constrained('payroll_salary_employee');
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
        Schema::dropIfExists('payroll_salary_employee_edeductions');
    }
};
