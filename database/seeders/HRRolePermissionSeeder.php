<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HRRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = config('hr_permissions');
        $monthlySummaryPermission = Permission::firstOrCreate([
            'name' => 'payroll.monthly-summary.view',
            'guard_name' => 'web',
        ]);

        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "hr.$group.$action", 'guard_name' => 'web']);
            }
        }

        // HR roles
        $roles = ['hr_admin', 'hr_clerk', 'hr_manager'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo(Permission::where('name', 'like', 'hr.%')->get());
            $role->givePermissionTo($monthlySummaryPermission);

            $name = str_replace('_', ' ', $roleName);

            // Create First a Super Admin user
            $hr_manager = User::firstOrCreate(
                ['email' => $roleName.'@dost-tapi.com'],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                ]
            );

            // Assign role to user
            if (! $hr_manager->hasRole('super_admin')) {
                $hr_manager->assignRole($role);
            }
        }
    }
}
