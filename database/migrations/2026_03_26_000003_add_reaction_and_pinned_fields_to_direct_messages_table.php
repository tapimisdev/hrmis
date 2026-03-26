<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('reaction', 20)->nullable()->after('attachment_type');
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
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pinned_by_id');
            $table->dropColumn(['reaction', 'pinned_at']);
        });
    }
};
