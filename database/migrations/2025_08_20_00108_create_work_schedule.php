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
        Schema::create('work_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_monday')
                ->default(false);
            $table->boolean('is_tuesday')
                ->default(false);
            $table->boolean('is_wednesday')
                ->default(false);
            $table->boolean('is_thursday')
                ->default(false);
            $table->boolean('is_friday')
                ->default(false);
            $table->boolean('is_saturday')
                ->default(false);
            $table->boolean('is_sunday')
                ->default(false);
            $table->boolean('is_active')
                ->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedule');
    }
};
