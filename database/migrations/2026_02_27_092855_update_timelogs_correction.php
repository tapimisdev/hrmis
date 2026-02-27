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
        Schema::table('timelog_corrections', function (Blueprint $table) {
            $table->text('action_remarks')
                ->nullable()
                ->after('status');

            $table->enum('concern', [
                'system_out_of_order',
                'failure_to_entry',
                'incorrect_entry'
            ])
            ->nullable()
            ->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timelog_corrections', function (Blueprint $table) {
            $table->dropColumn(['action_remarks', 'concern']);
        });
    }
};