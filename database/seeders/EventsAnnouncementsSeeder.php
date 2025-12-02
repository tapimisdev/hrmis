<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventsAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Seed 10 announcements
        for ($i = 1; $i <= 10; $i++) {

            $title = fake()->sentence(6);
            $slug = Str::slug($title . '-' . $i);

            // Insert into main table
            $eventId = DB::table('events_announcements')->insertGetId([
                'title'         => $title,
                'banner'        => 'hello_announcement.png',   // image inside public folder
                'slug'          => $slug,
                'description'   => fake()->paragraph(5),
                'posted_on'     => now()->subDays(rand(1, 30)),
                'email_notif'   => fake()->boolean(),
                'push_notif'    => fake()->boolean(),
                'show_viewers'  => fake()->boolean(),
                'is_suspension' => fake()->boolean(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Insert tags (1–3 tags per announcement)
            foreach (range(1, rand(1, 3)) as $t) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $eventId,
                    'name'       => fake()->word(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert posted_by (random user IDs, adjust based on your users table)
            DB::table('events_announcements_posted_by')->insert([
                'user_id'               => 3,
                'event_announcement_id' => $eventId,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // Insert attachments (0–2 attachments)
            foreach (range(1, rand(0, 2)) as $a) {
                DB::table('events_announcements_attachments')->insert([
                    'event_announcement_id' => $eventId,
                    'filename'  => 'sample_file_' . $a . '.pdf',
                    'title'     => fake()->sentence(3),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }

            // Insert viewers (0–5 viewers)
            foreach (range(1, rand(0, 9)) as $v) {
                DB::table('events_announcements_viewers')->insert([
                    'event_announcement_id' => $eventId,
                    'user_id'  => rand(6,13),
                    'viewed_at'=> now()->subMinutes(rand(5, 2000)),
                ]);
            }
        }
    }
}
