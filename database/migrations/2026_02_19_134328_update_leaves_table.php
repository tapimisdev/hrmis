<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leaves', function(Blueprint $table) {
            $table->enum('cummulative_type', [
                'monthly',
                'yearly',
                'none'
            ])->after('is_cumulative');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function(Blueprint $table) {
            $table->dropColumn('cummulative_type');
        });
    }
};
