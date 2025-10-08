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
        Schema::create('suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('events_announcements_id')
                ->nullable()
                ->constrained('events_announcements')
                ->onDelete('set null');
            $table->date('date');
            $table->enum('type', [
                'whole_day',
                'half_day',
            ]);
            $table->time('from_time')
                ->nullable();
            $table->time('to_time')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspensions');
    }
};
