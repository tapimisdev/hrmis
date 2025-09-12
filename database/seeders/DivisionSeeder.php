<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $data = [
            [
                'code' => 'OD',
                'name' => 'Office of the Director',
                'description' => "Responsible for the overall direction, supervision and operations of all units of the Institute.",
            ],
            [
                'code' => 'TIPD',
                'name' => 'Technology Information and Promotion Division',
                'description' => "Responsible for the elevation of level of awareness and the promotion of the innovation system strategies.",
            ],
            [
                'code' => 'IBOD',
                'name' => 'Investment & Business Operation Division',
                'description' => "Responsible in providing financial support and business operations' improvement services.",
            ],
            [
                'code' => 'IDD',
                'name' => 'Invention Development Division',
                'description' => "Responsible in improving financial support for the use of the intellectual property system and business development of inventions and technologies.",
            ],
            [
                'code' => 'FAD',
                'name' => 'Finance and Administration Division',
                'description' => "Responsible for the financial and administration support services.",
            ],
        ];

        foreach ($data as $division) {
            DB::table('divisions')->updateOrInsert(
                ['code' => $division['code']], 
                $division                  
            );
        }
    }
}
