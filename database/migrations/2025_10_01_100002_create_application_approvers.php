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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id'); 
            $table->foreignId('leave_application_id')
                ->constrained('leave_applications')
                ->onDelete('cascade');
            $table->unsignedInteger('user_id');
            $table->integer('total_approvers');
            $table->integer('no_approved');
            $table->boolean('isApproved')
                ->default(false);
            $table->timestamps();
        });

        Schema::create('applications_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->onDelete('cascade');
            $table->foreignId( 'user_id')
                ->constrained('users')
                ->onDelete('cascade');
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications_approvals');
        Schema::dropIfExists('applications');
    }
};
