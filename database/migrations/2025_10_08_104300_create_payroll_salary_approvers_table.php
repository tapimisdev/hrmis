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
        Schema::create('payroll_salary_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_salary_id')->constrained('payroll_salary')->onDelete('cascade');
            $table->foreignId('user_id')->constrained();    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_approvers');
    }
};
