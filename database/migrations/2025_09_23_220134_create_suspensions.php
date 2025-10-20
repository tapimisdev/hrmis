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
        Schema::create('suspension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('events_announcements_id')
                ->nullable();
            $table->string('name');
            $table->longText('description')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('suspension_dates', function(Blueprint $table) {
            $table->id();
            $table->foreignId('suspension_id')
                ->constrained('suspension')
                ->onDelete('cascade');
            $table->date('date');
            $table->enum('type', [
                'whole_day',
                'half_day',
            ]);
            $table->enum('shift', [
                'morning',
                'afternoon'
            ])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspension_dates');
        Schema::dropIfExists('suspension');
    }
};
