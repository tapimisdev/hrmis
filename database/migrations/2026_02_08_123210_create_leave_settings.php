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
        Schema::create('leaves_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')
                ->nullable()
                ->constrained('leaves')
                ->onDelete('set null');
            $table->foreignId('deduct_credit_id')
                ->nullable()
                ->constrained('leaves')
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves_settings');
    }
};
