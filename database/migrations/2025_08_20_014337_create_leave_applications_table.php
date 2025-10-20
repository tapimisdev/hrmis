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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id'); 
            $table->string('employee_no')->nullable();
            $table->foreignId('leave_id')->constrained('leaves');
            $table->integer('days')->default(1);
            $table->text('reason');
            $table->enum('status', ['cancelled', 'pending', 'approved', 'rejected'])->default('pending');
            $table->longText('remarks')
                ->nullable();
            $table->unsignedBigInteger('approver_id')
                ->nullable();
            $table->integer('level'); 
            $table->timestamps();
        });

        Schema::create('leave_dates', function(Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')
                ->constrained('leave_applications')
                ->onDelete('cascade');
            $table->date('date');
        });

        Schema::create('leave_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')
                ->constrained('leave_applications')
                ->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable(); 
            $table->string('file_type')->nullable();
        });

        Schema::create('leave_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')
                ->constrained('leave_applications')
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
            $table->longText('remarks')
                ->nullable();
            $table->timestamp('action_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_attachments');
        Schema::dropIfExists('leave_dates');
        Schema::dropIfExists('leave_applications');
    }
};
