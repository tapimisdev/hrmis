<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bir_2316', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('annual_tax_computation_id')->nullable();
            $table->year('taxable_year');

            $table->string('employee_no');
            $table->string('employee_name');
            $table->string('employee_tin')->nullable();
            $table->text('employee_address')->nullable();
            $table->string('position')->nullable();
            $table->string('employment_type')->nullable();

            $table->string('employer_name')->nullable();
            $table->string('employer_tin')->nullable();
            $table->text('employer_address')->nullable();
            $table->string('rdo_code')->nullable();

            $table->decimal('annual_basic_salary', 15, 2)->default(0);
            $table->decimal('hazard_pay', 15, 2)->default(0);
            $table->decimal('longevity_pay', 15, 2)->default(0);
            $table->decimal('government_bonuses', 15, 2)->default(0);
            $table->decimal('de_minimis', 15, 2)->default(0);
            $table->decimal('gross_compensation_income', 15, 2)->default(0);
            $table->decimal('tax_exempt_bonus', 15, 2)->default(0);
            $table->decimal('net_taxable_benefit', 15, 2)->default(0);
            $table->decimal('gross_taxable_income', 15, 2)->default(0);
            $table->decimal('allowable_deductions', 15, 2)->default(0);
            $table->decimal('net_taxable_income', 15, 2)->default(0);
            $table->decimal('annual_tax_due', 15, 2)->default(0);
            $table->decimal('tax_withheld', 15, 2)->default(0);
            $table->decimal('tax_refund_or_payable', 15, 2)->default(0);

            $table->json('snapshot_data')->nullable();

            $table->string('status')->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'taxable_year']);
            $table->index(['taxable_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bir_2316');
    }
};
