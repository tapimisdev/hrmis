<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch leave IDs keyed by name
        $leaves = DB::table('leaves')
            ->pluck('id', 'name'); // ['Vacation Leave' => 1, ...]

        $vacationLeaveId = $leaves['Vacation Leave'] ?? null;
        $mandatoryLeaveId = $leaves['Mandatory/Forced Leave'] ?? null;
        $sickLeaveId = $leaves['Sick Leave'] ?? null;

        $now = now();

        $data = [
            // Vacation Leave → self
            [
                'leave_id' => $vacationLeaveId,
                'deduct_credit_id' => $vacationLeaveId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Mandatory / Forced Leave → Vacation Leave
            [
                'leave_id' => $mandatoryLeaveId,
                'deduct_credit_id' => $vacationLeaveId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sick Leave → self
            [
                'leave_id' => $sickLeaveId,
                'deduct_credit_id' => $sickLeaveId,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // All others → NULL
            [
                'leave_id' => $leaves['Maternity Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Paternity Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Special Privilege Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Solo Parent Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Study Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['10-Day VAWC Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Rehabilitation Privilege'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Special Leave Benefits for Women'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Special Emergency (calamity) Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'leave_id' => $leaves['Adoption Leave'] ?? null,
                'deduct_credit_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('leaves_settings')->insert($data);
    }
}
