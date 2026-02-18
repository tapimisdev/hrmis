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
        Schema::table('leave_applications', function (Blueprint $table) {
            if (Schema::hasColumn('leave_applications', 'days')) {
                $table->dropColumn('days');
            }

            $table->string('credit_equivalent')
                ->nullable()
                ->after('leave_id');
            $table->string('credit_remarks')
                ->nullable()
                ->after('levels');
        });

        Schema::table('leave_dates', function(Blueprint $table) {
            $table->string('credit_equivalent')
                ->nullable()
                ->after('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_applications', 'days')) {
                $table->integer('days')->default(1)->after('leave_id');
            }

            $table->dropColumn(['credit_equivalent', 'credit_remarks']);
        });

         Schema::table('leave_dates', function(Blueprint $table) {
            $table->dropColumn('credit_equivalent');
        });
    }
};
