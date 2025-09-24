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
            Schema::create('events_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('banner');            
                $table->string('slug')->unique();      
                $table->longText('description'); 
                $table->timestamp('posted_on')
                    ->nullable();
                $table->boolean('email_notif')
                    ->default(false);
                $table->boolean('push_notif')
                    ->default(true);
                $table->boolean('show_viewers')
                    ->default(false);
                $table->boolean('is_suspension')
                    ->default(false);
                $table->timestamps();              
            });
            
            Schema::create('events_announcements_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_announcement_id')
                    ->constrained('events_announcements')
                    ->onDelete('cascade');
                $table->string('name');
                $table->timestamps();              
            });

            Schema::create('events_announcements_posted_by', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->foreignId('event_announcement_id')
                    ->constrained('events_announcements')
                    ->onDelete('cascade');
                $table->timestamps();
            });

            Schema::create('events_announcements_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_announcement_id')
                    ->constrained('events_announcements')
                    ->onDelete('cascade');
                $table->string('filename');
                $table->string('title')
                    ->nullable();
                $table->timestamps();              
            });

            Schema::create('events_announcements_viewers', function(Blueprint $table) {
                $table->id();
                $table->foreignId('event_announcement_id')
                    ->constrained('events_announcements')
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
            Schema::dropIfExists('events_announcements_viewers');
            Schema::dropIfExists('events_announcements_posted_by');
            Schema::dropIfExists('events_announcements_attachments');
            Schema::dropIfExists('events_announcements');
        }
    };
