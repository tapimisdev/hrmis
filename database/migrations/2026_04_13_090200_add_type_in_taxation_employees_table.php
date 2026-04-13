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
        Schema::table('taxation_employees', function (Blueprint $table) {
            $table->string('type')->after('year')->default('forecast'); // q2, q3, q4, forecast
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxation_employees', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
