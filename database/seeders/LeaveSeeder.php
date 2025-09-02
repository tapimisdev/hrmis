<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name'          => 'Sick Leave',
                'is_cumulative' => true,
                'no_of_days'    => 15,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Vacation Leave',
                'is_cumulative' => true,
                'no_of_days'    => 15,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Leave Privilleges',
                'is_cumulative' => false,
                'no_of_days'    => 3,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        ];

        DB::table('leaves')->upsert(
            $data,
            ['name'],
            ['is_cumulative', 'no_of_days', 'updated_at']
        );

    }
}
