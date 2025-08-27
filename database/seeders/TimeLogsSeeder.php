<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $logs = [
            // Day 1 (Normal 4 logs)
            ['user_id' => 3, 'date_time' => '2025-08-01 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-01 12:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-01 13:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-01 17:00:00', 'created_at' => $now, 'updated_at' => $now],

            // Day 2 (Normal 4 logs)
            ['user_id' => 3, 'date_time' => '2025-08-02 08:05:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-02 12:10:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-02 13:05:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-02 17:15:00', 'created_at' => $now, 'updated_at' => $now],

            // Day 3 (6 logs with mistake)
            ['user_id' => 3, 'date_time' => '2025-08-03 08:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-03 08:05:00', 'created_at' => $now, 'updated_at' => $now], // mistake: duplicate Time In
            ['user_id' => 3, 'date_time' => '2025-08-03 12:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-03 13:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-03 17:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-08-03 17:02:00', 'created_at' => $now, 'updated_at' => $now], // mistake: extra Time Out
        ];

        DB::table('timelogs')->insert($logs);
    }
}
