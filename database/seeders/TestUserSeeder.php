<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data = [
            'account' => [
                'name' => 'Angela Machicaz',
                'email' => 'test01@email.com',
                'password' => Hash::make('password')
            ],
            'information' => [
                'employee_no' => 'test123',
                'biometrics_id' => 123,
                'account_status' => 'active',
                'date_hired' => Carbon::now()->format('Y-m-d'),
                'division_id' => 5,
                'unit_id' => 1,
                'employment_type_id' => 2,
                'position_id' => 26,
                'shift_id' => 2,
                'work_schedule_id' => 1,
                'salary_method' => 'cash',
                'salary' => '42,000',
            ],
            'personal' => [
                'firstname' => 'Angela',
                'lastname' => 'Machicaz',
                'age' => 30,
            ]
        ];

        $user = DB::table('users')->where('email', $data['account']['email'])->first();

        if (!$user) {
            $user_id = DB::table('users')->insertGetId([
                'name' => $data['account']['name'],
                'email' => $data['account']['email'],
                'password' => $data['account']['password'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_information')->insert([
                'user_id' => $user_id,
                'employee_no' => $data['information']['employee_no'],
                'biometrics_id' => $data['information']['biometrics_id'],
                'account_status' => $data['information']['account_status'],
                'date_hired' => $data['information']['date_hired'],
                'division_id' => $data['information']['division_id'],
                'unit_id' => $data['information']['unit_id'],
                'employment_type_id' => $data['information']['employment_type_id'],
                'position_id' => $data['information']['position_id'],
                'shift_id' => $data['information']['shift_id'],
                'work_schedule_id' => $data['information']['work_schedule_id'],
                'salary_method' => $data['information']['salary_method'],
                'salary' => $data['information']['salary'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_personal')->insert([
                'employee_no' => $data['information']['employee_no'],
                'firstname' => $data['personal']['firstname'],
                'lastname' => $data['personal']['lastname'],
                'age' => $data['personal']['age'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }

    }
}
