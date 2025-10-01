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
            $table->unsignedBigInteger('user_id'); // Employee who filed the leave
            $table->string('employee_no')->nullable();
            $table->foreignId('leave_id')->constrained('leaves');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days')->default(1);
            $table->text('reason');
            $table->enum('status', ['cancelled', 'pending', 'approved', 'rejected'])->default('pending');
            $table->tinyText('remarks')
                ->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_application_id');
            $table->string('file_path'); // stored file path
            $table->string('file_name')->nullable(); // original filename
            $table->string('file_type')->nullable(); // mime type (jpg, pdf, etc.)
            $table->timestamps();

            // Foreign key
            $table->foreign('leave_application_id')
                ->references('id')
                ->on('leave_applications')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_attachments');
        Schema::dropIfExists('leave_applications');
    }
};
