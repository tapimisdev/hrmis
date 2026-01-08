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
        Schema::create('timelog_corrections', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time_in')->nullable();
            $table->dateTime('break_out')->nullable();
            $table->dateTime('break_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->dateTime('overtime_in')->nullable();
            $table->dateTime('overtime_out')->nullable();
            $table->foreignId('shift_id')->constrained('shifts')->nullable();
            $table->foreignId('work_schedule_id')->constrained('work_schedule')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timelog_corrections');
    }
};
