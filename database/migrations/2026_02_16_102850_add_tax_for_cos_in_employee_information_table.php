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
        Schema::table('employee_information', function (Blueprint $table) {
            $table->boolean('two_percent')->after('toUpdatePassword')->default(false);
            $table->boolean('three_percent')->after('two_percent')->default(false);
            $table->boolean('five_percent')->after('three_percent')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->dropColumn(['two_percent', 'three_percent', 'five_percent']);
        });
    }
};
