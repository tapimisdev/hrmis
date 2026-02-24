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
        Schema::table('employee_salary', function (Blueprint $table) {
            $table->string('salary_method')
                ->nullable()
                ->after('deduction_applied');
        });

        Schema::table('employee_information', function (Blueprint $table) {
            if (Schema::hasColumn('employee_information', 'salary_method')) {
                $table->dropColumn('salary_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salary', function (Blueprint $table) {
            if (Schema::hasColumn('employee_salary', 'salary_method')) {
                $table->dropColumn('salary_method');
            }
        });
    
        Schema::table('employee_information', function (Blueprint $table) {
            $table->string('salary_method')
                ->nullable()
                ->after('deduction_applied');
        });
    }
};