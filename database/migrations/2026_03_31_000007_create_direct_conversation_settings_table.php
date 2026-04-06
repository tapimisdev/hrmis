<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_conversation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->string('nickname', 120)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_conversation_settings');
    }
};
