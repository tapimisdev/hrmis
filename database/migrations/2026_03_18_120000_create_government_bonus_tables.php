<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_bonus_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->enum('computation_type', ['fixed', 'percentage', 'formula', 'manual'])->default('manual');
            $table->decimal('computation_value', 12, 2)->nullable();
            $table->text('formula_expression')->nullable();
            $table->text('computation_notes')->nullable();
            $table->enum('service_date_basis', ['organization', 'company'])->default('organization');
            $table->boolean('require_active_account')->default(true);
            $table->unsignedInteger('min_years_of_service')->nullable();
            $table->boolean('require_work_shift')->default(true);
            $table->boolean('require_information')->default(true);
            $table->boolean('require_salary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_government_bonus', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->nullable();
            $table->string('label');
            $table->string('payroll_no');
            $table->string('month');
            $table->integer('no_employee')->default(0);
            $table->foreignId('employment_type_id')
                ->constrained('employment_types', indexName: 'pgb_employment_type_fk')
                ->onDelete('cascade');
            $table->foreignId('government_bonus_type_id')
                ->constrained('government_bonus_types', indexName: 'pgb_bonus_type_fk');
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('processed_by_id')
                ->constrained('users', indexName: 'pgb_processed_by_fk')
                ->onDelete('cascade');
            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'for_releasing',
                'completed',
                'cancelled',
                'failed',
            ])->default('draft');
            $table->timestamps();
        });

        Schema::create('payroll_government_bonus_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_government_bonus_id')
                ->constrained('payroll_government_bonus', indexName: 'pgbe_payroll_fk')
                ->onDelete('cascade');
            $table->foreignId('government_bonus_type_id')
                ->constrained('government_bonus_types', indexName: 'pgbe_bonus_type_fk');
            $table->string('employee_no');
            $table->string('name');
            $table->string('position');
            $table->decimal('bonus_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('adjustments', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2);
            $table->longText('remarks')->nullable();
        });

        Schema::create('payroll_government_bonus_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_government_bonus_id')
                ->constrained('payroll_government_bonus', indexName: 'pgba_payroll_fk')
                ->onDelete('cascade');
            $table->string('level');
            $table->foreignId('user_id')->constrained(indexName: 'pgba_user_fk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_government_bonus_approvers');
        Schema::dropIfExists('payroll_government_bonus_employee');
        Schema::dropIfExists('payroll_government_bonus');
        Schema::dropIfExists('government_bonus_types');
    }
};
