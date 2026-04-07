<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->foreignId('reply_to_id')
                ->nullable()
                ->after('body')
                ->constrained('group_messages')
                ->nullOnDelete();
            $table->string('reaction', 20)->nullable()->after('reply_to_id');
            $table->timestamp('pinned_at')->nullable()->after('reaction');
            $table->foreignId('pinned_by_id')
                ->nullable()
                ->after('pinned_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
            $table->dropConstrainedForeignId('pinned_by_id');
            $table->dropColumn(['reaction', 'pinned_at']);
        });
    }
};
