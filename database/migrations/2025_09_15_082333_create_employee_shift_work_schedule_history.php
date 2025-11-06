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
        Schema::create('employee_shift_work_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no');
            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->onDelete('restrict');
            $table->foreignId('work_schedule_id')
                ->constrained('work_schedule')
                ->onDelete('restrict');
            $table->date('effectivity_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_shift_work_schedule');
    }
};
