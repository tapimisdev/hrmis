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
        Schema::table('suspension', function (Blueprint $table) {
            $table->boolean('is_active')->after('description')->default(true);
        });
        Schema::table('suspension_dates', function (Blueprint $table) {
            $table->boolean('is_active')->after('shift')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suspension', function (Blueprint $table) {
            $table->dropIfExists('is_active');
        });

         Schema::table('suspension_dates', function (Blueprint $table) {
            $table->dropIfExists('is_active');
        });
    }
};
