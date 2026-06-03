<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_violations', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_violations', 'status')) {
                $table->string('status')->default('unseen')->after('details');
            }

            if (! Schema::hasColumn('employee_violations', 'seen_at')) {
                $table->timestamp('seen_at')->nullable()->after('status');
            }
        });

        DB::table('employee_violations')
            ->whereNull('status')
            ->update(['status' => 'unseen']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_violations', function (Blueprint $table) {
            if (Schema::hasColumn('employee_violations', 'seen_at')) {
                $table->dropColumn('seen_at');
            }

            if (Schema::hasColumn('employee_violations', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
