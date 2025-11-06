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
        Schema::create('employee_leave_credits', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->foreignId('leave_id')
                ->nullable()
                ->constrained('leaves')
                ->onDelete('set null');
            $table->string('amount')
                ->nullable();
            $table->timestamp('effectivity_date');
            $table->timestamps();
        });

        Schema::create('employee_leave_card', function(Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->string('period');
            $table->string('year');
            $table->longText('particulars')
                ->nullable();
            $table->string('vl_earned');
            $table->string('vl_aut_w_pay');
            $table->string('vl_bal');
            $table->string('vl_aut_wo_pay');

            $table->string('sl_earned');
            $table->string('sl_aut_w_pay');
            $table->string('sl_bal');
            $table->string('sl_aut_wo_pay');

            $table->longText('remarks')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_credits');
        Schema::dropIfExists('employee_leave_card');
    }
};
