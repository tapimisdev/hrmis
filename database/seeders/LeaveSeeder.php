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
                'name'                 => 'Sick Leave',
                'is_cumulative'        => true,
                'credit_to_deduct'     => 1.25,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'name'                 => 'Vacation Leave',
                'is_cumulative'        => true,
                'credit_to_deduct'     => 1.25,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'name'                 => 'Privilleges Leave',
                'is_cumulative'        => true,
                'credit_to_deduct'     => 1.25,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ];

        DB::table('leaves')->upsert(
            $data,
            ['name'],
            ['is_cumulative', 'updated_at']
        );

    }
}
