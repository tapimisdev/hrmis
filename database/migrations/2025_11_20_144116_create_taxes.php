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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('icon')
                ->required();
            $table->string('slug')
                ->unique();
            $table->string('name')
                ->unique();
            $table->timestamps();
        });

        Schema::create('tax_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');
            $table->year('year');
            $table->timestamps();
        });

        Schema::create('employee_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_deduction_id')->constrained('tax_years')->onDelete('cascade');
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
        Schema::dropIfExists('employee_taxes');
        Schema::dropIfExists('tax_years');
        Schema::dropIfExists('taxes');
    }

};
