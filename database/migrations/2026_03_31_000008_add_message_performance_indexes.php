<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->index(['sender_id', 'recipient_id', 'id'], 'direct_messages_sender_recipient_id_idx');
            $table->index(['recipient_id', 'sender_id', 'read_at', 'id'], 'direct_messages_recipient_sender_read_id_idx');
            $table->index(['pinned_at', 'id'], 'direct_messages_pinned_id_idx');
        });

        Schema::table('group_messages', function (Blueprint $table) {
            $table->index(['group_chat_id', 'id'], 'group_messages_group_chat_id_id_idx');
            $table->index(['group_chat_id', 'pinned_at', 'id'], 'group_messages_group_chat_pinned_id_idx');
            $table->index(['group_chat_id', 'sender_id', 'created_at'], 'group_messages_group_chat_sender_created_idx');
        });

        Schema::table('group_chat_members', function (Blueprint $table) {
            $table->index(['user_id', 'group_chat_id'], 'group_chat_members_user_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('group_chat_members', function (Blueprint $table) {
            $table->dropIndex('group_chat_members_user_group_idx');
        });

        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropIndex('group_messages_group_chat_id_id_idx');
            $table->dropIndex('group_messages_group_chat_pinned_id_idx');
            $table->dropIndex('group_messages_group_chat_sender_created_idx');
        });

        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('direct_messages_sender_recipient_id_idx');
            $table->dropIndex('direct_messages_recipient_sender_read_id_idx');
            $table->dropIndex('direct_messages_pinned_id_idx');
        });
    }
};
