<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftAndWeeklyScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $now = Carbon::now();

        // Shifts
        $shifts = [
            [
                'name'                   => '7AM-9AM Regular Flexible',
                'earliest_time'          => '07:00:00',
                'start_time'             => '09:00:00',
                'break_out_time'         => '12:00:00',
                'break_in_time'          => '13:00:00',
                'end_time'               => null,
                'minimum_overtime_hours' => 1.00,
                'is_flexible'            => true,
                'is_break_required'      => true,
                'is_night_shift'         => false,
                'is_active'              => true,
            ],
            [
                'name'                   => '7AM-8PM COS Flexible',
                'earliest_time'          => '07:00:00',
                'start_time'             => '08:00:00',
                'break_out_time'         => '12:00:00',
                'break_in_time'          => '13:00:00',
                'end_time'               => null,
                'minimum_overtime_hours' => 0,
                'is_flexible'            => true,
                'is_break_required'      => true,
                'is_night_shift'         => false,
                'is_active'              => true,
            ]
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->updateOrInsert(
                ['name' => $shift['name']], // unique by name
                array_merge($shift, [
                    'updated_at' => $now,
                    'created_at' => DB::raw("COALESCE(created_at, '{$now}')"),
                ])
            );
        }

        // Work Schedules
        $workSchedules = [
            [
                'name'         => 'Monday to Friday',
                'is_monday'    => true,
                'is_tuesday'   => true,
                'is_wednesday' => true,
                'is_thursday'  => true,
                'is_friday'    => true,
                'is_saturday'  => false,
                'is_sunday'    => false,
                'is_active'    => true,
            ],
            [
                'name'         => 'Monday to Saturday',
                'is_monday'    => true,
                'is_tuesday'   => true,
                'is_wednesday' => true,
                'is_thursday'  => true,
                'is_friday'    => true,
                'is_saturday'  => true,
                'is_sunday'    => false,
                'is_active'    => true,
            ],
        ];

        foreach ($workSchedules as $schedule) {
            DB::table('work_schedule')->updateOrInsert(
                ['name' => $schedule['name']], // unique by name
                array_merge($schedule, [
                    'updated_at' => $now,
                    'created_at' => DB::raw("COALESCE(created_at, '{$now}')"),
                ])
            );
        }
    }
}
