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
        Schema::create('subsistence_allowance_records', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('full_day_count', 8, 2)->default(0);
            $table->decimal('half_day_count', 8, 2)->default(0);
            $table->decimal('below_four_hours_count', 8, 2)->default(0);
            $table->decimal('actual_days', 8, 2)->default(0);
            $table->decimal('deduction_count', 8, 2)->default(0);
            $table->decimal('deduction_amount', 12, 2)->default(0);
            $table->decimal('computed_amount', 12, 2)->default(0);
            $table->boolean('required_facility_service')->default(false);
            $table->boolean('available_at_all_times')->default(false);
            $table->boolean('may_leave_breaks')->default(false);
            $table->boolean('on_leave')->default(false);
            $table->boolean('on_official_travel')->default(false);
            $table->boolean('provided_meals')->default(false);
            $table->boolean('is_eligible')->default(false);
            $table->json('eligibility_details')->nullable();
            $table->longText('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_no', 'month', 'year'], 'sa_record_employee_period_unique');
            $table->index(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsistence_allowance_records');
    }
};
