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
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('icon')
                ->required();
            $table->enum('type', [
                'earnings',
                'taxes'
            ]);
            $table->string('slug')
                ->unique();
            $table->string('name')
                ->unique();
            $table->timestamps();
        });

        Schema::create('payroll_components_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_component_id')
                ->constrained('payroll_components')
                ->onDelete('cascade');
            $table->year('year');
            $table->timestamps();
        });

        Schema::create('employee_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_deduction_id')
                ->constrained('payroll_components_years')
                ->onDelete('cascade');
            $table->string('employee_no');
            $table->decimal('amount', 12, 2);
            $table->string('month');
            $table->timestamps();
        });

      
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_components');
        Schema::dropIfExists('payroll_components_years');
        Schema::dropIfExists('payroll_components');
    }

};
