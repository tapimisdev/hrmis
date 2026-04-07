<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->enum('message_type', ['direct', 'group'])->default('group');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 10); // Store emoji
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['message_id', 'message_type']);
            $table->unique(['message_id', 'message_type', 'user_id'], 'unique_reaction_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
