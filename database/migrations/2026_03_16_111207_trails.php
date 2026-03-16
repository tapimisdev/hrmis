<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actioned_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actioned_by_name')->nullable(); 
            $table->string('method');
            $table->string('controller')->nullable();
            $table->longText('description')->nullable();
            $table->json('payload')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trails');
    }
};