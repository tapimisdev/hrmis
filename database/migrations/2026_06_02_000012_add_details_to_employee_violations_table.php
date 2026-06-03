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
        Schema::table('employee_violations', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_violations', 'details')) {
                $table->json('details')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_violations', function (Blueprint $table) {
            if (Schema::hasColumn('employee_violations', 'details')) {
                $table->dropColumn('details');
            }
        });
    }
};
