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
        Schema::create('application_approver', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'overtime',
                'leave',
                'pass_slip'
            ]);
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->onDelete('cascade');
            $table->foreignId('unit_id')
                ->nullable()
                ->contrained('units')
                ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('application_approver_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_approver_id')
                ->constrained('application_approver')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->integer('level')
                ->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_approver_user');
        Schema::dropIfExists('application_approver_org');
        Schema::dropIfExists('application_approver');
    }
};
