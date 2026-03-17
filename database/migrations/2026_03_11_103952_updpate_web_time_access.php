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
        Schema::table('web_time_access', function (Blueprint $table) {
            $table->boolean('isRequiredAccomplishment')
                ->default(false)
                ->after('days_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_time_access', function (Blueprint $table) {
            $table->dropColumn('isRequiredAccomplishment');
        });
    }
};