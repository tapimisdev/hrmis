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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('earliest_time')->nullable();
            $table->time('start_time');
            $table->time('break_out_time')->nullable();
            $table->time('break_in_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('minimum_overtime_hours', 5, 2)->default(0);
            $table->boolean('is_flexible')->default(true);
            $table->boolean('is_break_required')->default(true);
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
