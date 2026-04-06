<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_message_id')->constrained('group_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 10); // Store emoji
            $table->timestamps();

            // Ensure one reaction per user per message
            $table->unique(['group_message_id', 'user_id']);
            $table->index(['group_message_id', 'reaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_message_reactions');
    }
};
