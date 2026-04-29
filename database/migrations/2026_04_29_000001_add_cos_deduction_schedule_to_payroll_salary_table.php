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
        Schema::table('payroll_salary', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_salary', 'apply_deduction')) {
                $table->boolean('apply_deduction')->default(true)->after('is_aut_deducted');
            }

            if (!Schema::hasColumn('payroll_salary', 'deduction_deferred_cutoff')) {
                $table->enum('deduction_deferred_cutoff', ['first_cutoff', 'second_cutoff'])
                    ->nullable()
                    ->after('apply_deduction');
            }

            if (!Schema::hasColumn('payroll_salary', 'deduction_deferred_date')) {
                $table->date('deduction_deferred_date')->nullable()->after('deduction_deferred_cutoff');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_salary', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_salary', 'deduction_deferred_date')) {
                $table->dropColumn('deduction_deferred_date');
            }

            if (Schema::hasColumn('payroll_salary', 'deduction_deferred_cutoff')) {
                $table->dropColumn('deduction_deferred_cutoff');
            }

            if (Schema::hasColumn('payroll_salary', 'apply_deduction')) {
                $table->dropColumn('apply_deduction');
            }
        });
    }
};
