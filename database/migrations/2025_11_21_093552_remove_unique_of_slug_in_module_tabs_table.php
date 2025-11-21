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
        Schema::table('module_tabs', function (Blueprint $table) {
            $table->dropUnique('module_tabs_tab_slug_unique');
            $table->dropUnique('module_tabs_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_tabs', function (Blueprint $table) {
            $table->unique('tab_slug');
            $table->unique('tabs_order');
        });
    }
};
