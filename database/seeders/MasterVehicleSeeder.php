<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterVehicleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('master_vehicles')->delete();

        DB::table('master_vehicles')->insert([
            [
                'id'             => 1,
                'vehicle_number' => 'B 9123 XYZ',
                'vehicle_type'   => 'Box Truck',
                'driver_name'    => 'Budi Santoso',
                'is_active'      => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => 2,
                'vehicle_number' => 'D 5678 ABC',
                'vehicle_type'   => 'Container',
                'driver_name'    => 'Ahmad Yusuf',
                'is_active'      => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => 3,
                'vehicle_number' => 'B 1234 DEF',
                'vehicle_type'   => 'Pickup',
                'driver_name'    => 'Slamet Riyadi',
                'is_active'      => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
