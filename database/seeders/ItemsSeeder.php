<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('items')->delete();

        DB::table('items')->insert([
            [
                'id'         => 1,
                'item_code'  => '149',
                'item_name'  => '10OZ MCFLURRY CUP PIE ALA MODE NEW',
                'uom'        => 'PCS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 2,
                'item_code'  => '150',
                'item_name'  => '16OZ CLEAR COLD DRINK CUP',
                'uom'        => 'PCS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 3,
                'item_code'  => '201',
                'item_name'  => 'SOUP BOWL 22OZ WITH LID',
                'uom'        => 'PCS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 4,
                'item_code'  => '08100-305',
                'item_name'  => 'PLASTIC STRAW 21CM WRAPPED',
                'uom'        => 'PCS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
