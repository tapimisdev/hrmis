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
            $table->string('leave_type');
            $table->string('employee_no');
            $table->string('period');
            $table->string('year');
            $table->longText('particulars')
                ->nullable();
            $table->float('earned');
            $table->float('deduction');
            $table->float('balance');
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
