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

        Schema::table('offset_dates', function(Blueprint $table) {
            $table->boolean('isActive')
                ->default(true)
                ->after('date'); 
        });

        Schema::table('leave_dates', function(Blueprint $table) {
            $table->boolean('isActive')
                ->default(true)
                ->after('date'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offset_dates', function(Blueprint $table) {
            $table->dropColumn('isActive');
        });

        Schema::table('leave_dates', function(Blueprint $table) {
            $table->dropColumn('isActive');
        });
    }
};
