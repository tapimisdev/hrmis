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
        Schema::create('taxations', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->foreignId('hazard_tax_id')
                ->constrained('payroll_components');
            $table->foreignId('salary_tax_id')
                ->constrained('payroll_components');
            $table->foreignId('longevity_id')
                ->constrained('payroll_components');
            $table->foreignId('train_law_id')
                ->constrained('train_law');
                    
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

            $table->boolean('is_active');
            
            $table->timestamps();

            $table->index(['year', 'is_active']);
        });

        Schema::create('taxation_other_earnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_id')
                ->constrained('taxations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->decimal('amount', 15, 2);

            $table->enum('tax_type', ['taxable', 'non_taxable', 'exempt'])
                ->default('taxable');

            $table->timestamps();
        });

        Schema::create('taxation_other_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('taxation_id')
                ->constrained('taxations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->decimal('amount', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxation_other_earnings');
        Schema::dropIfExists('taxation_other_deductions');
        Schema::dropIfExists('taxations');
    }
};
