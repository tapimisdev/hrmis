<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_conversation_clears', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('cleared_before_message_id')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'partner_id']);
        });

        Schema::table('group_chat_members', function (Blueprint $table) {
            $table->unsignedBigInteger('cleared_before_message_id')->nullable()->after('last_read_at');
            $table->timestamp('cleared_at')->nullable()->after('cleared_before_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_chat_members', function (Blueprint $table) {
            $table->dropColumn(['cleared_before_message_id', 'cleared_at']);
        });

        Schema::dropIfExists('direct_conversation_clears');
    }
};
