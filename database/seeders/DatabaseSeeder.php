<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MenuSeeder::class,
            MasterVehicleSeeder::class,
            ItemsSeeder::class,             // harus sebelum TWH & SO seeder
            TemporaryWarehouseSeeder::class,
            SalesOrderSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@local'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );

        $user->assignRole('Super Admin');
    }
}
