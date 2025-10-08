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
        Schema::create('payroll_salary', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_no');
            $table->enum('cutoff', ['first_cutoff', 'second_cutoff']);
            $table->string('period_covered');
            $table->integer('no_employee');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('deduction_amount', 12, 2);
            $table->decimal('netpay_amount', 12, 2);
            $table->date('payroll_date');
            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'for_releasing',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_salary');
    }
};
