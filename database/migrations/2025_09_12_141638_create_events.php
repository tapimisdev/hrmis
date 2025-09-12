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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description');
            $table->string('thumbnail');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->date('date');
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->onDelete('set null');
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->onDelete('set null');
            $table->foreignId('employment_type_id')
                ->nullable()
                ->constrained('employment_types')
                ->onDelete('set null');
            $table->foreignId('position_id')
                ->nullable()
                ->constrained('positions')
                ->onDelete('set null');
            $table->timestamp('posted_at');
            $table->boolean('email_notif')
                ->default(false);
            $table->boolean('push_notif')
                ->default(true);
            $table->boolean('show_viewers')
                ->default(false);
            $table->timestamps();
        });

        Schema::create('events_attachments', function(Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->onDelete('cascade');
            $table->string('name');
        });

        Schema::create('events_viewers', function(Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_viewers');
        Schema::dropIfExists('events_attachments');
        Schema::dropIfExists('events');
    }
};
