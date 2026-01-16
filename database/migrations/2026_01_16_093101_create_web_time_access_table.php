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
        Schema::create('web_time_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_no');
            
            $table->boolean('always')->default(false);        // always allowed
            $table->json('specific_dates')->nullable();       // multiple specific dates, e.g., ["2026-01-20","2026-01-22"]
            $table->json('days_of_week')->nullable();        // e.g., ["Mon","Wed","Fri"]
            
            // Effectivity start date — the rule starts from here and continues indefinitely
            $table->dateTime('effectivity_date');

            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_time_access');
    }
};
