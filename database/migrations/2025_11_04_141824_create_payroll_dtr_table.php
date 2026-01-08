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
        Schema::create('payroll_dtr', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_salary_id')->constrained('payroll_salary');
            $table->string('user_id');
            $table->string('employee_no');

            $table->integer('total_hours')->default(0);
            $table->integer('incomplete_logs')->default(0);
            $table->integer('pending_leaves')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('late_undertime')->default(0);
            $table->integer('absent')->default(0);
            $table->integer('leaves')->default(0);
            $table->integer('holiday')->default(0);
            $table->integer('suspensions')->default(0);
            $table->integer('excess')->default(0);
            $table->integer('actual_presence')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_dtr');
    }
};
