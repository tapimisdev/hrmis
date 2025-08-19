<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $hrRole = Role::firstOrCreate(['name' => 'hr']);

        // Admin = all
        $adminRole->syncPermissions(Permission::all());

        // HR = HRIS + Timekeeping + Reports
        $hrRole->syncPermissions([
            'view hris', 'create hris', 'edit hris', 'delete hris',
            'view timekeeping', 'approve timekeeping', 'reject timekeeping',
            'view reports', 'export reports',
        ]);

        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'dev05@dost-tapi.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('d0$t2025'),
            ]
        );
        $admin->assignRole($adminRole);

        // HR User
        $hr = User::firstOrCreate(
            ['email' => 'hr@dost-tapi.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('d0$t2025'),
            ]
        );
        $hr->assignRole($hrRole);
    }
}
