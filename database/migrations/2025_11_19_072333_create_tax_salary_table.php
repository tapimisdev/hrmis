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
        Schema::create('tax_salary', function (Blueprint $table) {
            $table->id();
            
            $table->year('year');
            $table->timestamps();
        });

        Schema::create('tax_salary_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_salary_id')->constrained('tax_salary');
            $table->string('employee_no');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 12, 2);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_salary_employee');
        Schema::dropIfExists('tax_salary');
    }
};
