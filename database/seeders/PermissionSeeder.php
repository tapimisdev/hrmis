<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'payroll_components' => [
                'view',
                'create',
                'update',
                'delete'
            ],
            'payroll_settings' => [
                    'view',
                    'update'
            ],
        ];

        // Generate permissions automatically
        foreach ($permissions as $group => $actions) {
            foreach ($actions as $action) {
                Permission  ::firstOrCreate(['name' => "admin.$group.$action", 'guard_name' => 'web']);
            }
        }
    }

}
