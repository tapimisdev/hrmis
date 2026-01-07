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
        Schema::create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')
                ->constrained('leaves')
                ->onDelete('cascade');
            $table->string('employee_no');
            $table->decimal('previous', 10, 2)->default(0);
            $table->decimal('earned', 10, 2)->default(0);
            $table->decimal('deducted', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('as_of');
            $table->longText('remarks')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_credits');
    }
};
