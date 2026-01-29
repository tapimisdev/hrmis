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
        // Drop table if exists
        if (Schema::hasTable('notifications')) {
            Schema::drop('notifications');
        }

        // Create table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'event',
                'application',
                'message',
                'approved',
                'rejected',
                'removed',
                'processing',
                'system'
            ]);
            $table->string('sender')
                ->nullable();              
            $table->string('receiver')
                ->default('*')
                ->nullable(); 
            $table->json('data');                  
            // $table->boolean('is_read')
            //     ->default(false); 
            $table->timestamp('read_at')
                ->nullable();
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');  // Assuming users table exists
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);  // Ensure one read record per user per notification
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('notifications');
    }
};
