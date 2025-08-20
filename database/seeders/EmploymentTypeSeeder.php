<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'code' => 'PL',
                'name' => 'Plantilla',
                'description' => 'Regular employment type for plantilla positions.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'COS',
                'name' => 'Contract of Service',
                'description' => 'Employment type for contract of service positions.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('employment_types')->upsert(
            $data,
            ['code'], 
            ['name']  
        );

    }
}
