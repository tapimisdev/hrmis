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
        Schema::create('offset_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->string('name');
            $table->unsignedBigInteger('user_id'); 
            $table->string('employee_no');
            $table->integer('days')->default(1);
            $table->text('reason');
            $table->enum('status', ['cancelled', 'pending', 'approved', 'rejected'])->default('pending');
            $table->longText('remarks')
                ->nullable();
            $table->unsignedBigInteger('approver_id')
                ->nullable();
            $table->integer('level')
                ->nullable();
            $table->json('levels')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('offset_dates', function(Blueprint $table) {
            $table->id();
            $table->foreignId('offset_application_id')
                ->constrained('offset_applications')
                ->onDelete('cascade');
            $table->enum('shift', [
                'morning',
                'afternoon',
                'wholeday'
            ]);
            $table->date('date');
        });

        Schema::create('offset_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offset_application_id')
                ->constrained('offset_applications')
                ->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable(); 
            $table->string('file_type')->nullable();
        });

        Schema::create('offset_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('offset_application_id')
                ->constrained('offset_applications')
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
        Schema::dropIfExists('offset_approvals');
        Schema::dropIfExists('offset_attachments');
        Schema::dropIfExists('offset_dates');
        Schema::dropIfExists('offset_applications');
    }
};
