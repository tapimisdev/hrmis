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
        $modules = [
            'hris' => ['view', 'create', 'edit', 'delete'],
            'timekeeping' => ['view', 'file', 'approve', 'reject'],
            'service' => ['view', 'generate', 'edit'],
            'payroll' => ['view', 'generate', 'edit'],
            'reports' => ['view', 'export'],
            'users' => ['view', 'create', 'edit', 'delete'],
        ];

        // Generate permissions automatically
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => $action . ' ' . $module]);
            }
        }
    }

}
