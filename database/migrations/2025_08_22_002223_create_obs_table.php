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
        Schema::create('obs', function (Blueprint $table) {
            $table->id();
            $table->string('obs_no')->unique();                 // e.g., OBS-2025-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // requester/employee
            $table->date('date_from');
            $table->date('date_to');
            $table->time('time_out')->nullable();               // start time
            $table->time('time_in')->nullable();                // end time
            $table->string('destination');                      // place, office, city
            $table->string('purpose', 500);                     // short purpose/subject
            $table->string('mode_of_transport')->nullable();    // company car, taxi, etc.
            $table->decimal('estimated_expense', 12, 2)->default(0);
            $table->string('charge_ to')->nullable();            // cost center / department

            // Approval flow
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->longText('remarks')
                ->nullable();
            $table->longText('approval_remarks')
                ->nullable();
            $table->unsignedBigInteger('approver_id')
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
            $table->unsignedBigInteger('obs_id');
            $table->string('file_path'); // stored file path
            $table->string('file_name')->nullable(); // original filename
            $table->string('file_type')->nullable(); // mime type (jpg, pdf, etc.)
            $table->timestamps();

            // Foreign key
            $table->foreign('obs_id')
                ->references('id')
                ->on('obs')
                ->onDelete('cascade');
        });

        Schema::create('obs_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('obs_id')
                ->constrained('obs')
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
        Schema::dropIfExists('obs');
    }
};
