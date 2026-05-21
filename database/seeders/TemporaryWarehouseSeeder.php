<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemporaryWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('trolley_histories')->delete();
        DB::table('trolley_items')->delete();
        DB::table('trolleys')->delete();
        DB::table('fgw_racks')->delete();
        DB::table('packing_units')->delete();
        DB::table('production_orders')->delete();
        DB::table('items')->delete();

        DB::table('items')->insert([
            'id' => 1,
            'item_code' => '07400-149',
            'item_name' => '10OZ MCFLURRY CUP PIE ALA MODE NEW',
            'uom' => 'PCS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('production_orders')->insert([
            'id' => 1,
            'spk_number' => 'SPK-P2-20260218-001',
            'production_date' => '2026-02-20',
            'item_id' => 1,
            'planned_qty' => 100,
            'status' => 'READY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fgw_racks')->insert([
            [
                'id' => 1,
                'rack_code' => 'RAK-A01',
                'rack_name' => 'Rak A01',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'rack_code' => 'RAK-A02',
                'rack_name' => 'Rak A02',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'rack_code' => 'RAK-B01',
                'rack_name' => 'Rak B01',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'rack_code' => 'RAK-B02',
                'rack_name' => 'Rak B02',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $packingUnits = [];

        for ($i = 1; $i <= 40; $i++) {
            $running = 10004395 + $i;
            $barcode = (string) ($running + 270005000);

            $packingUnits[] = [
                'production_order_id' => 1,
                'item_id' => 1,
                'box_number' => 'BOX 005-' . $running,
                'barcode' => $barcode,
                'print_batch_id' => 'PB-DUMMY-20260220',
                'qty' => 1000,
                'uom' => 'PCS',
                'printed_at' => now(),
                'printed_by' => 1,
                'status' => 'PRINTED',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('packing_units')->insert($packingUnits);

        DB::table('trolleys')->insert([
            [
                'id' => 1,
                'trolley_code' => 'TRL-20260220-0001',
                'barcode' => 'TR202602200001',
                'capacity' => 20,
                'status' => 'OPEN',
                'fgw_rack_id' => null,
                'received_fgw_at' => null,
                'received_fgw_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('delivery_orders')->insert([
            [
                'id' => 1,
                'so_number' => 'SO-20260220-001',
                'do_number' => 'DO-20260220-001',
                'customer_name' => 'PT. ABC',
                'truck_number' => 'B 9123 XYZ',
                'status' => 'READY',
                'loaded_at' => null,
                'loaded_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('delivery_order_items')->insert([
            [
                'delivery_order_id' => 1,
                'item_id' => 1,
                'required_boxes' => 20,
                'loaded_boxes' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
