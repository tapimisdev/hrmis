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
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->decimal('previous', 10, 3)->default(0)->change();
            $table->decimal('earned', 10, 3)->default(0)->change();
            $table->decimal('deducted', 10, 3)->default(0)->change();
            $table->decimal('balance', 10, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->decimal('previous', 10, 2)->default(0)->change();
            $table->decimal('earned', 10, 2)->default(0)->change();
            $table->decimal('deducted', 10, 2)->default(0)->change();
            $table->decimal('balance', 10, 2)->default(0)->change();
        });
    }
};
