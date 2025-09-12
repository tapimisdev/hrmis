<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => ''
            ]
        ];

        foreach($data as $country) {
            DB::table('countries')->updateOrInsert([
                'name' => $country['name']
            ], [
                'name' => $country['name']
            ]);
        }
    }
}
