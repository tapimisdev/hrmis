<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('message_type', 20)
                ->default('user')
                ->after('recipient_id');
            $table->foreignId('visible_to_user_id')
                ->nullable()
                ->after('message_type')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['sender_id', 'recipient_id', 'visible_to_user_id', 'created_at'], 'direct_messages_visibility_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('direct_messages_visibility_created_at_idx');
            $table->dropConstrainedForeignId('visible_to_user_id');
            $table->dropColumn('message_type');
        });
    }
};
