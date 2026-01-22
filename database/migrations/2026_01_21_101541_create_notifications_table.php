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
            ]);
            $table->string('sender');              
            $table->string('receiver')
                ->default('*')
                ->nullable(); 
            $table->json('data');                  
            $table->boolean('is_read')
                ->default(false); 
            $table->timestamp('read_at')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
