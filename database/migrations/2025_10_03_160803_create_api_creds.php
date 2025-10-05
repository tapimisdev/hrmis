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
        Schema::create('api_creds', function (Blueprint $table) {
            $table->id();
            $tabe->enum('type', [
                'smtp',
                'sms'
            ]);
            $table->string('username');
            $table->string('port')
                ->nullable();
            $table->string('enc_type')
                ->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_creds');
    }
};
