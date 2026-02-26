<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_salary_employee', function (Blueprint $table) {
            $columns = ['position', 'salary_grade', 'employee_no', 'name'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payroll_salary_employee', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = ['position', 'salary_grade', 'employee_no', 'name'];

        // Set default values for NULLs before making NOT NULL
        foreach ($columns as $column) {
            DB::table('payroll_salary_employee')
                ->whereNull($column)
                ->update([$column => 'N/A']);
        }

        Schema::table('payroll_salary_employee', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('payroll_salary_employee', $column)) {
                    $table->string($column)->nullable(false)->change();
                }
            }
        });
    }
};