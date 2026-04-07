<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->string('system_actor_label', 120)
                ->nullable()
                ->after('system_key');
            $table->string('system_target_label', 120)
                ->nullable()
                ->after('system_actor_label');
        });
    }

    public function down(): void
    {
        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropColumn(['system_actor_label', 'system_target_label']);
        });
    }
};
