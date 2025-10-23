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
        Schema::create('overtime_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('employee_no');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['cancelled', 'pending', 'approved', 'rejected'])->default('pending');
            $table->longText('remarks')->nullable();
            $table->integer('level');
            $table->json('levels');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_applications_id')  // fixed FK name to match first table
                ->constrained('overtime_applications')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->integer('level');
            $table->enum('status', [
                'cancelled',
                'pending',
                'approved',
                'rejected'
            ])->default('pending');
            $table->longText('remarks')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();  // added timestamps for consistency
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('overtime_approvals');
        Schema::dropIfExists('overtime_applications');
    }
};
