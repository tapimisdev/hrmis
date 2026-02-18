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
        Schema::table('offset_applications', function (Blueprint $table) {
            if (Schema::hasColumn('offset_applications', 'days')) {
                $table->dropColumn('days');
            }

            $table->string('credit_equivalent')
                ->nullable()
                ->after('employee_no');
            $table->string('credit_remarks')
                ->nullable()
                ->after('levels');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offset_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('offset_applications', 'days')) {
                $table->integer('days')->default(1)->after('employee_no');
            }

            $table->dropColumn(['credit_equivalent', 'credit_remarks']);
        });
    }
};
