<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            'menus.view',
            'menus.create',
            'menus.edit',
            'menus.delete',

            'temporary-warehouse.view',
            'temporary-warehouse.scan',

            'fgd.view',
            'fgd.scan',

            'loading.view',
            'loading.scan',
            'loading.print-document',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $adminLogistic = Role::firstOrCreate([
            'name' => 'Admin Logistic',
            'guard_name' => 'web',
        ]);

        $warehouse = Role::firstOrCreate([
            'name' => 'Temporary Warehouse',
            'guard_name' => 'web',
        ]);

        $fgd = Role::firstOrCreate([
            'name' => 'FGD',
            'guard_name' => 'web',
        ]);

        $loading = Role::firstOrCreate([
            'name' => 'Loading',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $warehouse->syncPermissions([
            'dashboard.view',
            'temporary-warehouse.view',
            'temporary-warehouse.scan',
        ]);

        $fgd->syncPermissions([
            'dashboard.view',
            'fgd.view',
            'fgd.scan',
        ]);

        $loading->syncPermissions([
            'dashboard.view',
            'loading.view',
            'loading.scan',
            'loading.print-document',
        ]);

        $adminLogistic->syncPermissions([
            'dashboard.view',
            'temporary-warehouse.view',
            'fgd.view',
            'loading.view',
            'loading.print-document',
        ]);
    }
}
