<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_message_id')->constrained('direct_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 10); // Store emoji
            $table->timestamps();

            // Ensure one reaction per user per message
            $table->unique(['direct_message_id', 'user_id']);
            $table->index(['direct_message_id', 'reaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_message_reactions');
    }
};
