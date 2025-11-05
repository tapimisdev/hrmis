<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $data = [
            'FAD' => [
                [
                    'code' => 'MIS',
                    'name' => 'Management Information System Unit',
                    'description' => 'Provides information technology support, system development, and database management services for the Institute.',
                ],
                [
                    'code' => 'GAD',
                    'name' => 'Gender and Development Unit',
                    'description' => 'Ensures the integration of gender perspectives and the implementation of GAD-related programs, projects, and activities within the Institute.',
                ],
                [
                    'code' => 'Legal',
                    'name' => 'Legal Unit',
                    'description' => 'Provides legal assistance and services, including contract review, compliance, and institutional legal matters.',
                ],
                [
                    'code' => 'Legal Propel',
                    'name' => 'Legal Unit Propel',
                    'description' => 'Provides specialized legal support services specific to the PROPEL program and its technology commercialization initiatives.',
                ],
            ],
        ];

        foreach ($data as $division => $units) {

            $divisionRecord = DB::table('divisions')->where('code', $division)->first();

            if ($divisionRecord) {
                $division_id = $divisionRecord->id;

                foreach ($units as $unit) {
                    DB::table('units')->updateOrInsert(
                        [
                            'code' => $unit['code'], 
                        ],
                        [
                            'name' => $unit['name'],
                            'division_id' => $division_id,
                        ]
                    );
                }
            }
        }
    }
}
