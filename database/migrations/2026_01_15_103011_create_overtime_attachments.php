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
        Schema::create('overtime_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_applications_id')
                ->constrained('overtime_applications')
                ->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable(); 
            $table->string('file_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_attachments');
    }
};
