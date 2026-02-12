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
        Schema::create('special_order_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id'); 
            $table->string('employee_no');
            $table->string('so_no');
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

        Schema::create('special_order_dates', function(Blueprint $table) {
            $table->id();
            $table->foreignId('special_order_application_id')
                ->constrained('special_order_applications')
                ->onDelete('cascade');
            $table->enum('shift', [
                'morning',
                'afternoon',
                'wholeday'
            ]);
            $table->date('date');
            $table->boolean('isActive')
                ->default(true);
        });

         Schema::create('special_order_attachments', function(Blueprint $table) {
            $table->id();
            $table->foreignId('special_order_application_id')
                ->constrained('special_order_applications')
                ->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable(); 
            $table->string('file_type')->nullable();
        });

        Schema::create('special_order_approvals', function(Blueprint $table) {
            $table->id();
            $table->foreignId('special_order_application_id')
                ->constrained('special_order_applications')
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
        Schema::dropIfExists('special_order_approvals');
        Schema::dropIfExists('special_order_attachments');
        Schema::dropIfExists('special_order_dates');
        Schema::dropIfExists('special_order_applications');
    }
};
