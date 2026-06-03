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
        Schema::create('employee_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employee_no')->nullable();
            $table->foreignId('violation_setting_id')->nullable()->constrained('violation_settings')->nullOnDelete();
            $table->string('violation_type');
            $table->string('action_name');
            $table->unsignedInteger('threshold');
            $table->unsignedInteger('occurrence_count');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->text('description');
            $table->json('details')->nullable();
            $table->string('status')->default('unseen');
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_violations');
    }
};
