<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agency = [
            'code' => 'TAPI',
            'name' => 'Technology Application and Promotion Institute',
            'description' => 'The Technology Application and Promotion Institute (TAPI) created by virtue of Executive Order No. 128 on 30 January 1987, is one of DOST\'s service agencies whose primary responsibility is to promote the commercialization of technologies and market the services of other operating units of the Department.'
        ];

        $divisions = [];
        $units = [];

        DB::table('agency')->insert($agency);

    }
}
