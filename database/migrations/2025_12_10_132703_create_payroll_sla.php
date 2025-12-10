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
         Schema::create('payroll_sla_pay', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->nullable();
            $table->string('label');
            $table->string('payroll_no');
            $table->string('month');
            $table->integer('no_employee')
                ->default(0);
            $table->foreignId('employment_type_id')
                ->constrained('employment_types')
                ->onDelete('cascade');
            $table->string('total');
            $table->foreignId('processed_by_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'for_releasing',
                'completed',
                'cancelled',
                'failed',
            ]);
            $table->timestamps();
        });

        Schema::create('payroll_sla_pay_employee', function(Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_sla_pay_id')
                ->constrained('payroll_sla_pay')
                ->onDelete('cascade');
            $table->string('employee_no');
            $table->string('name');
            $table->string('position');
            $table->decimal('subsistence_allowance', 12, 2);
            $table->decimal('laundry_allowance', 12, 2);
            $table->decimal('total_sla', 12, 2);
            $table->decimal('ut_deductions', 12, 2);
            $table->decimal('uniform_deduction', 12, 2);
            $table->decimal('healthcard', 12, 2);
            $table->decimal('adjustments', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->longText('remarks')
                ->nullable();
        });

        Schema::create('payroll_sla_pay_approvers', function(Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_sla_pay_id')
                ->constrained('payroll_sla_pay')
                ->onDelete('cascade');
            $table->string('level');
            $table->foreignId('user_id')->constrained();    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_sla_pay_approvers');
        Schema::dropIfExists('payroll_sla_pay_employee');
        Schema::dropIfExists('payroll_sla_pay');
    }
};
