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
        Schema::create('payroll_components_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'salary_pay',
                'hazard_pay',
                'longetivity_pay',
            ]);
            $table->foreignId('payroll_components_id')
                ->constrained('payroll_components')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_components_settings');
    }
}; 
