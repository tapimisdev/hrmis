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
        Schema::create('employee_salary', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->foreignId('tranche_id')
                ->constrained('tranche')
                ->onDelete('restrict');
            
            $table->integer('salary_grade');

            $table->integer('step')
                ->nullable();

            $table->enum('salary_frequency', [
                'once',
                'twice'
            ]);

            $table->enum('salary_cutoff', [
                'first_cutoff',
                'second_cutoff',
                'both'
            ]);

            $table->enum('deduction_applied', [
                'first_cutoff',
                'second_cutoff',
                'both'
            ])->default('both');

            $table->enum('salary_basis', [
                'monthly',
                'daily'
            ])->nullable();

            $table->string('amount');
            $table->string('daily_rate');
            $table->date('effectivity_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary');
    }
};
