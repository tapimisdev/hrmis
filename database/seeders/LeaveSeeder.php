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
                'name' => 'Vacation Leave',
                'is_cumulative' => true,
                'cummulative_type' => 'monthly',
                'to_be_credited' => 1.25,
                'showCreditsESS' => true,
                'description' => 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => 'Sick Leave',
                'is_cumulative' => true,
                'cummulative_type' => 'monthly',
                'to_be_credited' => 1.25,
                'showCreditsESS' => true,
                'description' => 'Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => 'Mandatory/Forced Leave',
                'is_cumulative' => true,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => 'Wellness Leave',
                'is_cumulative' => true,
                'cummulative_type' => 'yearly',
                'to_be_credited' => 5.00,
                'showCreditsESS' => true,
                'description' => 'CSC Resolution',
            ],
            [
                'name' => 'Special Privilege Leave',
                'is_cumulative' => true,
                'cummulative_type' => 'yearly',
                'to_be_credited' => 3.00,
                'showCreditsESS' => true,
                'description' => 'Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => 'Maternity Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'R.A. No. 11210 / IRR issued by CSC, DOLE and SSS',
            ],
            [
                'name' => 'Paternity Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended',
            ],
            [
                'name' => 'Solo Parent Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'RA No. 8972 / CSC MC No. 8, s. 2004',
            ],
            [
                'name' => 'Study Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => '10-Day VAWC Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'RA No. 9262 / CSC MC No. 15, s. 2005',
            ],
            [
                'name' => 'Rehabilitation Privilege',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292',
            ],
            [
                'name' => 'Special Leave Benefits for Women',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'RA No. 9710 / CSC MC No. 25, s. 2010',
            ],
            [
                'name' => 'Special Emergency (calamity) Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'CSC MC No. 2, s. 2012, as amended',
            ],
            [
                'name' => 'Adoption Leave',
                'is_cumulative' => false,
                'cummulative_type' => 'none',
                'to_be_credited' => 0,
                'showCreditsESS' => false,
                'description' => 'R.A. No. 8552',
            ],
        ];

        foreach ($data as $leave) {
            DB::table('leaves')->updateOrInsert(
                ['name' => $leave['name']], 
                [                         
                    'is_cumulative'    => $leave['is_cumulative'],
                    'cummulative_type' => $leave['cummulative_type'],
                    'to_be_credited'   => $leave['to_be_credited'],
                    'showCreditsESS' => $leave['showCreditsESS'],
                    'description'    => $leave['description'],
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }

    }
}
