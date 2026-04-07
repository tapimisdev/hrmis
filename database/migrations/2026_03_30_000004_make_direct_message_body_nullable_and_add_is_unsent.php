<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE direct_messages MODIFY body TEXT NULL');

        Schema::table('direct_messages', function ($table) {
            $table->boolean('is_unsent')
                ->default(false)
                ->after('unsent_at');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function ($table) {
            $table->dropColumn('is_unsent');
        });

        DB::statement('ALTER TABLE direct_messages MODIFY body TEXT NOT NULL');
    }
};
