<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('read_at');
            $table->timestamp('unsent_at')->nullable()->after('edited_at');
            $table->foreignId('unsent_by_id')
                ->nullable()
                ->after('unsent_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unsent_by_id');
            $table->dropColumn(['edited_at', 'unsent_at']);
        });
    }
};
