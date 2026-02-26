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
        Schema::create('taxation_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxation_id')
                ->constrained('taxations')
                ->cascadeOnDelete();

            $table->year('year');
            $table->string('employee_no');

            $table->boolean('mid_year');
            $table->boolean('year_end');
            $table->boolean('longevity');
            $table->boolean('hazard_pay');

            $table->boolean('less_bir_rr3_2015');

            $table->boolean('allowable_gsis');
            $table->boolean('allowable_philhealth');
            $table->boolean('allowable_pagibig');

            $table->unsignedTinyInteger('portion_hazard_pay');
            $table->unsignedTinyInteger('portion_basic_pay');
            $table->unsignedTinyInteger('portion_longevity_pay');

            $table->decimal('amount_gross', 15, 2)->default(0);
            $table->decimal('amount_annual_total_allowables', 15, 2)->default(0);

            $table->decimal('amount_annual_taxable', 15, 2)->default(0);
            $table->decimal('amount_annual_tax', 15, 2)->default(0);
            $table->decimal('amount_monthly_tax', 15, 2)->default(0);

            $table->decimal('amount_portion_hazard_pay', 15, 2)->default(0);
            $table->decimal('amount_portion_basic_pay', 15, 2)->default(0);
            $table->decimal('amount_portion_longevity_pay', 15, 2)->default(0);

            $table->decimal('amount_mid_year_bonus', 15, 2)->default(0);
            $table->decimal('amount_year_end_bonus', 15, 2)->default(0);
            $table->decimal('amount_longevity_pay', 15, 2)->default(0);
            $table->decimal('amount_hazard_pay', 15, 2)->default(0);

            $table->decimal('amount_other_earnings_taxable', 15, 2)->default(0);
            $table->decimal('amount_other_earnings_non_taxable', 15, 2)->default(0);
            
            $table->decimal('amount_other_deductions', 15, 2)->default(0);

            $table->json('remarks')->nullable();

            $table->json('raw_payload')->nullable();

            $table->boolean('is_active');
            $table->timestamps();
        });

        Schema::create('taxation_employee_other_earnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_employee_id')
                ->constrained('taxation_employees')
                ->cascadeOnDelete();

            $table->string('name');
            $table->decimal('amount', 15, 2);

            $table->enum('tax_type', ['taxable', 'non_taxable', 'exempt'])
                ->default('taxable');

            $table->timestamps();
        });

        Schema::create('taxation_employee_other_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_employee_id')
                ->constrained('taxation_employees')
                ->cascadeOnDelete();

            $table->string('name');
            $table->decimal('amount', 15, 2);

            $table->timestamps();
        });

        Schema::create('taxation_employee_bonus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_employee_id')
                ->constrained('taxation_employees')
                ->cascadeOnDelete();

            // Reference
            $table->string('employee_no');
            $table->string('type'); // midyear | year_end | etc.

            // Computation Date
            $table->date('as_of');

            // Salary Details
            $table->decimal('basic_salary_as_of', 15, 2);
            $table->date('salary_effective_date');

            // Eligibility
            $table->boolean('eligible')->default(false);
            $table->integer('months_of_service')->default(0);

            // Service Period
            $table->date('service_start');
            $table->date('service_end');

            // Computation Result
            $table->decimal('amount', 15, 2)->default(0);

            $table->text('reason')->nullable();

            $table->timestamps();

            // indexing
            $table->index(['employee_no', 'type']);
        });

        Schema::create('tax_computation_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_employee_id')
                ->constrained('taxation_employees')
                ->cascadeOnDelete();
                
            // Identifiers
            $table->string('employee_no', 50)->index();

            // Core amounts (use DECIMAL for money accuracy)
            $table->decimal('annual_income', 15, 2)->default(0);
            $table->decimal('fixed_tax', 15, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0); // e.g., 20.00
            $table->decimal('excess_over', 15, 2)->default(0);
            $table->decimal('excess_amount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);

            $table->decimal('monthly_tax', 15, 2)->default(0);


            // Bracket range (stored as decimals, not strings)
            $table->decimal('bracket_from', 15, 2)->nullable();
            $table->decimal('bracket_to', 15, 2)->nullable();

            // Metadata / notes
            $table->string('remarks', 255)->nullable();

            // store the entire raw payload for auditing/debugging
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            // indexing
            $table->index(['employee_no', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_computation_logs');
        Schema::dropIfExists('taxation_employee_bonus');
        Schema::dropIfExists('taxation_employee_other_deductions');
        Schema::dropIfExists('taxation_employee_other_earnings');
        Schema::dropIfExists('taxation_employees');
    }
};
