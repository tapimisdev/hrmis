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
        Schema::create('obs_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();         
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // requester/employee
            $table->string('employee_no');
            $table->date('date_from');
            $table->date('date_to');
            $table->time('time_out')->nullable();         
            $table->time('time_in')->nullable();               
            $table->string('destination');                     
            $table->string('purpose', 500);                
            $table->string('mode_of_transport')->nullable();   
            $table->decimal('estimated_expense', 12, 2)->default(0);
            $table->string('charge_to')->nullable();         
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->longText('remarks')
                ->nullable();
            $table->longText('approval_remarks')
                ->nullable();
            $table->unsignedBigInteger('approver_id')
                ->nullable(); 
            $table->integer('level')
                ->nullable();
            $table->json('levels')
                ->nullable();
            $table->timestamp('approved_at')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'date_from', 'date_to']);
        });

        Schema::create('obs_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obs_applications_id');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable(); 
            $table->timestamps();

            $table->foreign('obs_applications_id')
                ->references('id')
                ->on('obs_applications')
                ->onDelete('cascade');
        });

        Schema::create('obs_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('obs_applications_id')
                ->constrained('obs_applications')
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
        Schema::dropIfExists('obs_approvals');
        Schema::dropIfExists('obs_attachments');
        Schema::dropIfExists('obs_applications');
    }
};
