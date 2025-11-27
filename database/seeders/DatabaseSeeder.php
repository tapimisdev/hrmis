<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleSeeder::class,
            HRRolePermissionSeeder::class,
            EmployeeRolePermissionSeeder::class,
            SuperAdminRolePermissionSeeder::class,
            ShiftAndWeeklyScheduleSeeder::class,
            EmploymentTypeSeeder::class,
            OrganizationSeeder::class,
            LeaveSeeder::class,
            DivisionSeeder::class,
            UnitSeeder::class,
            PositionSeeder::class,
            TestUserSeeder::class,
            CountriesSeeder::class,
            ModulesSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
