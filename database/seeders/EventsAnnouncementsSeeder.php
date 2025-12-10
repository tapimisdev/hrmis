<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class EventsAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Seed 10 announcements
        for ($i = 1; $i <= 10; $i++) {

            $title = $faker->sentence(6);
            $slug = Str::slug($title . '-' . $i);

            // Insert into main table
            $eventId = DB::table('events_announcements')->insertGetId([
                'title'         => $title,
                'banner'        => 'hello_announcement.png', // image inside public folder
                'slug'          => $slug,
                'description'   => $faker->paragraph(5),
                'posted_on'     => now()->subDays(rand(1, 30)),
                'email_notif'   => $faker->boolean(),
                'push_notif'    => $faker->boolean(),
                'show_viewers'  => $faker->boolean(),
                'is_suspension' => $faker->boolean(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Insert tags (1–3 tags per announcement)
            $tagsCount = rand(1, 3);
            for ($t = 1; $t <= $tagsCount; $t++) {
                DB::table('events_announcements_tags')->insert([
                    'event_announcement_id' => $eventId,
                    'name'       => $faker->word(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert posted_by (random user IDs, adjust based on your users table)
            DB::table('events_announcements_posted_by')->insert([
                'user_id'               => 3, // Make sure this user exists
                'event_announcement_id' => $eventId,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // Insert attachments (0–2 attachments)
            $attachmentsCount = rand(0, 2);
            for ($a = 1; $a <= $attachmentsCount; $a++) {
                DB::table('events_announcements_attachments')->insert([
                    'event_announcement_id' => $eventId,
                    'filename'  => 'sample_file_' . $a . '.pdf',
                    'title'     => $faker->sentence(3),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }

            // Insert viewers (0–5 viewers)
            $viewersCount = rand(0, 5);
            for ($v = 1; $v <= $viewersCount; $v++) {
                DB::table('events_announcements_viewers')->insert([
                    'event_announcement_id' => $eventId,
                    'user_id'  => 1,
                    'viewed_at'=> now()->subMinutes(rand(5, 2000)),
                ]);
            }
        }
    }
}
