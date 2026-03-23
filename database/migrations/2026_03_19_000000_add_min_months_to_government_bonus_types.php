<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('government_bonus_types', function (Blueprint $table) {
            $table->unsignedInteger('min_months_of_service')->nullable()->after('min_years_of_service');
        });
    }

    public function down(): void
    {
        Schema::table('government_bonus_types', function (Blueprint $table) {
            $table->dropColumn('min_months_of_service');
        });
    }
};