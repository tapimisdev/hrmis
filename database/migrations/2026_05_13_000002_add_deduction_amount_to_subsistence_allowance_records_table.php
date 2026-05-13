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
        if (
            !Schema::hasTable('subsistence_allowance_records')
            || Schema::hasColumn('subsistence_allowance_records', 'deduction_amount')
        ) {
            return;
        }

        Schema::table('subsistence_allowance_records', function (Blueprint $table) {
            $table->decimal('deduction_amount', 12, 2)->default(0)->after('deduction_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            !Schema::hasTable('subsistence_allowance_records')
            || !Schema::hasColumn('subsistence_allowance_records', 'deduction_amount')
        ) {
            return;
        }

        Schema::table('subsistence_allowance_records', function (Blueprint $table) {
            $table->dropColumn('deduction_amount');
        });
    }
};
