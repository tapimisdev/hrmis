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
        Schema::table('special_order_applications', function(Blueprint $table) {
            $table->boolean('within_metro_manila')
                ->default(false)
                ->after('so_no');
            $table->boolean('isHazardous')
                ->default(false)
                ->after('within_metro_manila');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_order_applications', function (Blueprint $table) {
            $table->dropColumn('within_metro_manila');
        });
    }
};
