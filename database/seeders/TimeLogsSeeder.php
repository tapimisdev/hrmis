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

        // $logs = [
        //     // September 1 (Monday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-01 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-01 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-01 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-01 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 2 (Tuesday - Late 09:05)
        //     ['user_id' => 3, 'date_time' => '2025-09-02 09:05:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-02 12:05:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-02 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-02 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 3 (Wednesday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-03 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-03 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-03 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-03 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 4 (Thursday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-04 08:40:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-04 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-04 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-04 17:40:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 5 (Friday - Absent) → NO LOGS

        //     // September 8 (Monday - Late 09:15)
        //     ['user_id' => 3, 'date_time' => '2025-09-08 09:15:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-08 12:10:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-08 13:05:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-08 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 9 (Tuesday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-09 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-09 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-09 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-09 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 10 (Wednesday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-10 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-10 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-10 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-10 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 11 (Thursday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-11 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-11 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-11 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-11 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 12 (Friday - Undertime)
        //     ['user_id' => 3, 'date_time' => '2025-09-12 08:20:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-12 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-12 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-12 16:20:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

        //     // September 15 (Monday - Normal)
        //     ['user_id' => 3, 'date_time' => '2025-09-15 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-15 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-15 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        //     ['user_id' => 3, 'date_time' => '2025-09-15 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        // ];

        $logs = [
            // September 1 (Monday - Normal)
            ['user_id' => 3, 'date_time' => '2025-09-01 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-01 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-01 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-01 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Overtime entries
            ['user_id' => 3, 'date_time' => '2025-09-01 17:30:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-01 18:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 2 (Tuesday - Normal)
            ['user_id' => 3, 'date_time' => '2025-09-02 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-02 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-02 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-02 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 3 (Wednesday - Normal + 2 overtime logs)
            ['user_id' => 3, 'date_time' => '2025-09-03 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-03 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-03 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-03 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 4 (Thursday - Normal + 2 overtime logs)
            ['user_id' => 3, 'date_time' => '2025-09-04 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-04 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-04 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-04 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Overtime entries
            ['user_id' => 3, 'date_time' => '2025-09-04 17:30:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-04 18:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 5 (Friday - Absent) → NO LOGS

            // September 8 (Monday - Normal)
            ['user_id' => 3, 'date_time' => '2025-09-08 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-08 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-08 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-08 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 9 (Tuesday - Late 09:10)
            ['user_id' => 3, 'date_time' => '2025-09-09 09:10:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-09 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-09 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-09 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 10 (Wednesday - Normal)
            ['user_id' => 3, 'date_time' => '2025-09-10 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-10 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-10 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-10 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 11 (Thursday - Normal)
            ['user_id' => 3, 'date_time' => '2025-09-11 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-11 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-11 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-11 17:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            // September 12 (Friday - Undertime at 16:30)
            ['user_id' => 3, 'date_time' => '2025-09-12 08:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-12 12:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-12 13:00:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'date_time' => '2025-09-12 16:30:00', 'shift_id' => 1, 'work_schedule_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];


        foreach ($logs as $log) {
            DB::table('timelogs')->updateOrInsert(
                [
                    'user_id' => $log['user_id'],
                    'date_time' => $log['date_time'],
                    'shift_id' => $log['shift_id'],
                    'work_schedule_id' => $log['work_schedule_id'],
                ], 
                [
                    'created_at' => $log['created_at'],
                    'updated_at' => $log['updated_at'],
                ]
            );
        }

    }
}
