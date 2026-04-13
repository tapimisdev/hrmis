<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_salary_aut_leave_credit_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_salary_id');
            $table->foreignId('payroll_salary_permanent_employee_id', 'pspe_id_unique');
            $table->string('employee_no');
            $table->unsignedBigInteger('leave_id');
            $table->string('as_of', 7);
            $table->decimal('daily_rate', 12, 3)->default(0);
            $table->decimal('working_hours', 8, 3)->default(8);
            $table->decimal('aut_amount', 12, 3)->default(0);
            $table->decimal('equivalent_leave_credits', 12, 3)->default(0);
            $table->integer('total_minutes')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('payroll_salary_id', 'psalcd_payroll_salary_fk')
                ->references('id')
                ->on('payroll_salary')
                ->cascadeOnDelete();
            $table->foreign('payroll_salary_permanent_employee_id', 'psalcd_pspe_fk')
                ->references('id')
                ->on('payroll_salary_permanent_employees')
                ->cascadeOnDelete();
            $table->unique('payroll_salary_permanent_employee_id', 'payroll_salary_aut_leave_credit_deductions_pspe_unique');
            $table->index(['payroll_salary_id', 'employee_no'], 'payroll_salary_aut_leave_credit_deductions_payroll_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_aut_leave_credit_deductions');
    }
};
