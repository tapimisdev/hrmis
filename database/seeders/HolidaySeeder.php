<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'regular' => [
                [
                    'name' => '',
                    'date' => '',
                    'type' => '',
                    'is_repeating' => '',
                ]
            ],
            'special_working' => [
                [
                    'name' => '',
                    'date' => '',
                    'type' => '',
                    'is_repeating' => '',
                ]
            ],
            'special_non_working' => [
                [
                    'name' => '',
                    'date' => '',
                    'type' => '',
                    'is_repeating' => '',
                ]
            ],
            'company' => [
                [
                    'name' => '',
                    'date' => '',
                    'type' => '',
                    'is_repeating' => '',
                ]
            ]

        ];
    }
}
