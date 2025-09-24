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
            $table->string('cutoff_count'); // e.g., "1st Cutoff", "2nd Cutoff"
            $table->string('cuttoff_period');
            $table->date('payroll_date');
            $table->enum('status', [
                'draft', 'pending', 'approved', 'for_releasing', 'completed', 'cancelled'
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
