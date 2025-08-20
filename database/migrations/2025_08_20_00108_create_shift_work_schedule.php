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
        Schema::create('shift_schedule', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')
                ->nullable();
            $table->boolean('isFlexible')
                ->default(false);
            $table->string('shift_start');
            $table->string('lunch_start');
            $table->string('lunch_end');
            $table->string('shift_end');
            $table->string('min_ot')
                ->nullable();
            $table->string('ot_until')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('work_schedule', function (Blueprint $table) {
            $table->id();
            $table->boolean('isMon')
                ->default(false);
            $table->boolean('isTue')
                ->default(false);
            $table->boolean('isWed')
                ->default(false);
            $table->boolean('isThu')
                ->default(false);
            $table->boolean('isFri')
                ->default(false);
            $table->boolean('isSat')
                ->default(false);
            $table->boolean('isSun')
                ->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_schedule');
        Schema::dropIfExists('work_schedule');
    }
};
