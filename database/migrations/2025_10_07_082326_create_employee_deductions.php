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
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deduction_id')
                ->constrained('deductions')
                ->onDelete('cascade');
            $table->string('employee_no');
            $table->string('first_term')
                ->nullable();
            $table->string('second_term')
                ->nullable();
            $table->enum('type', [
                'daily',
                'monthly',
                'divided_by_22'
            ]);
            $table->timestamp('start_date');
            $table->timestamp('end_date')
                ->nullable();
            $table->boolean('isActive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
