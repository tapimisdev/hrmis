<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Create First a Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'dev05@dost-tapi.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
            ]
        );

        // Create Second a Super Admin user
        $superAdmin2 = User::firstOrCreate(
            ['email' => 'kemwell@gmail.com'], // change this to your preferred email
            [
                'name' => 'Admin Kim',
                'password' => Hash::make('password'), 
            ]
        );

        // Assign role to user
        if (! $superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole($superAdminRole);
        }

        // Assign role to user
        if (! $superAdmin2->hasRole('super_admin')) {
            $superAdmin2->assignRole($superAdminRole);
        }

        $this->command->info('Super Admin user created successfully!');
        $this->command->warn('Email: dev05@dost-tapi.com');
    }
}
