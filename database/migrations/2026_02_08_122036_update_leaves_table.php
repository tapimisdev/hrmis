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
        Schema::table('leaves', function(Blueprint $table) {
            $table->dropColumn('credit_to_deduct');
            $table->tinyText('description')
                ->nullable()
                ->after('name');
            $table->boolean('showCreditsESS')
                ->after('is_active')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->decimal('credit_to_deduct', 8, 2)->nullable();
            $table->dropColumn(['description', 'showCreditsESS']);
        });
    }
};
