<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'emp.behavioral_notices.view',
            'guard_name' => 'web',
        ]);

        foreach (['emp_contractual', 'emp_regular'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where([
            'name' => 'emp.behavioral_notices.view',
            'guard_name' => 'web',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
