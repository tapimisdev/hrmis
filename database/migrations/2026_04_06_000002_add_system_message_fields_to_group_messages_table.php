<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->string('system_key', 50)
                ->nullable()
                ->after('message_type');
            $table->foreignId('system_target_user_id')
                ->nullable()
                ->after('system_key')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['group_chat_id', 'message_type', 'system_key', 'created_at'],
                'group_messages_system_created_at_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropIndex('group_messages_system_created_at_idx');
            $table->dropConstrainedForeignId('system_target_user_id');
            $table->dropColumn('system_key');
        });
    }
};
