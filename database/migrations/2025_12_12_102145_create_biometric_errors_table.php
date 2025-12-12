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
        Schema::create('biometric_errors', function (Blueprint $table) {
            $table->id();
            
            $table->string('biometric_id')->nullable();   // $biodId
            $table->dateTime('date_time')->nullable();    // $timestamp
            $table->string('fn')->nullable();             // $fn
            $table->string('type')->nullable();           // $type
            $table->string('biometric_sn')->nullable();   // SN
            $table->text('raw_input')->nullable();        // full raw input
            $table->text('error_message')->nullable();    // Exception message
            $table->text('stack_trace')->nullable();      // Exception stack trace

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_errors');
    }
};
