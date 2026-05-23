<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        Menu::truncate();

        Menu::create([
            'name' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'bi bi-speedometer2',
            'permission_name' => 'dashboard.view',
            'sort_order' => 1,
        ]);

        $master = Menu::create([
            'name' => 'Master Data',
            'icon' => 'bi bi-database',
            'sort_order' => 10,
        ]);

        Menu::create([
            'parent_id' => $master->id,
            'name' => 'Users',
            'route' => 'users.index',
            'icon' => 'bi bi-people',
            'permission_name' => 'users.view',
            'sort_order' => 1,
        ]);

        Menu::create([
            'parent_id' => $master->id,
            'name' => 'Roles',
            'route' => 'roles.index',
            'icon' => 'bi bi-shield-lock',
            'permission_name' => 'roles.view',
            'sort_order' => 2,
        ]);

        Menu::create([
            'parent_id' => $master->id,
            'name' => 'Menus',
            'route' => 'menus.index',
            'icon' => 'bi bi-list',
            'permission_name' => 'menus.view',
            'sort_order' => 3,
        ]);

        $logistic = Menu::create([
            'name' => 'Logistic Flow',
            'icon' => 'bi bi-truck',
            'sort_order' => 20,
        ]);

        Menu::create([
            'parent_id' => $logistic->id,
            'name' => 'Temporary Warehouse',
            'route' => 'temporary-warehouse.index',
            'icon' => 'bi bi-box-seam',
            'permission_name' => 'temporary-warehouse.view',
            'sort_order' => 1,
        ]);

        Menu::create([
            'parent_id' => $logistic->id,
            'name' => 'TW Quality Control',
            'route' => 'temporary-warehouse-qc.index',
            'icon' => 'bi bi-clipboard-check',
            'permission_name' => 'temporary-warehouse-qc.view',
            'sort_order' => 2,
        ]);

        Menu::create([
            'parent_id' => $logistic->id,
            'name' => 'FGW',
            'route' => 'fgd.index',
            'icon' => 'bi bi-qr-code-scan',
            'permission_name' => 'fgd.view',
            'sort_order' => 3,
        ]);

        Menu::create([
            'parent_id' => $logistic->id,
            'name' => 'Loading',
            'route' => 'loading.index',
            'icon' => 'bi bi-truck-front',
            'permission_name' => 'loading.view',
            'sort_order' => 4,
        ]);

        Menu::create([
            'parent_id' => $logistic->id,
            'name' => 'Log Scan Dus',
            'route' => 'scan-log.index',
            'icon' => 'bi bi-journal-text',
            'permission_name' => 'scan-log.view',
            'sort_order' => 5,
        ]);
    }
}
