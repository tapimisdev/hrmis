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
            $table->string('date_hired_organization')
                ->nullable()
                ->after('account_status');
            $table->renameColumn('date_hired', 'date_hired_company')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->dropColumn('date_hired_organization');

            $table->renameColumn('date_hired_company', 'date_hired');
        });
    }
};
