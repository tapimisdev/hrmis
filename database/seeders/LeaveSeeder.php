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
                'name'             => 'Sick Leave',
                'is_cumulative'    => true,
                'credit_to_deduct' => 1.25,
            ],
            [
                'name'             => 'Vacation Leave',
                'is_cumulative'    => true,
                'credit_to_deduct' => 1.25,
            ],
            [
                'name'             => 'Privileges Leave',
                'is_cumulative'    => true,
                'credit_to_deduct' => 1.25,
            ],
        ];

        foreach ($data as $leave) {
            DB::table('leaves')->updateOrInsert(
                ['name' => $leave['name']], 
                [                         
                    'is_cumulative'    => $leave['is_cumulative'],
                    'credit_to_deduct' => $leave['credit_to_deduct'],
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }


    }
}
