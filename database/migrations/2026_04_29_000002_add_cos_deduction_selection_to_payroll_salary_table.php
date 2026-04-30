<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_salary', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_salary', 'deduction_apply_options')) {
                $table->json('deduction_apply_options')->nullable()->after('deduction_deferred_date');
            }

            if (!Schema::hasColumn('payroll_salary', 'deduction_defer_option')) {
                $table->string('deduction_defer_option')->nullable()->after('deduction_apply_options');
            }

            if (!Schema::hasColumn('payroll_salary', 'deduction_applied_payroll_id')) {
                $table->unsignedBigInteger('deduction_applied_payroll_id')->nullable()->after('deduction_defer_option');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_salary', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_salary', 'deduction_applied_payroll_id')) {
                $table->dropColumn('deduction_applied_payroll_id');
            }

            if (Schema::hasColumn('payroll_salary', 'deduction_defer_option')) {
                $table->dropColumn('deduction_defer_option');
            }

            if (Schema::hasColumn('payroll_salary', 'deduction_apply_options')) {
                $table->dropColumn('deduction_apply_options');
            }
        });
    }
};
