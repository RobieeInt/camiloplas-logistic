<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemporaryWarehouseSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan tabel terkait (urutan aman karena FK)
        DB::table('loading_items')->delete();
        DB::table('trolley_histories')->delete();
        DB::table('trolley_items')->delete();
        DB::table('trolleys')->delete();
        DB::table('fgw_racks')->delete();
        DB::table('packing_units')->delete();
        DB::table('delivery_order_items')->delete();
        DB::table('delivery_orders')->delete();
        DB::table('production_orders')->delete();
        // items di-handle oleh ItemsSeeder

        // ── Production Orders (3 SPK, masing-masing beda item) ─────────────
        DB::table('production_orders')->insert([
            [
                'id'              => 1,
                'sales_order_id'  => null, // di-link oleh SalesOrderSeeder
                'spk_number'      => 'SPK-P2-20260218-001',
                'production_date' => '2026-02-20',
                'item_id'         => 1, // 10OZ MCFLURRY CUP
                'planned_qty'     => 40000,
                'status'          => 'READY',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 2,
                'sales_order_id'  => null,
                'spk_number'      => 'SPK-P2-20260301-002',
                'production_date' => '2026-03-01',
                'item_id'         => 2, // 16OZ CLEAR COLD DRINK CUP
                'planned_qty'     => 25000,
                'status'          => 'READY',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 3,
                'sales_order_id'  => null,
                'spk_number'      => 'SPK-P2-20260310-003',
                'production_date' => '2026-03-10',
                'item_id'         => 3, // SOUP BOWL 22OZ WITH LID
                'planned_qty'     => 20000,
                'status'          => 'READY',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // ── FGW Racks ────────────────────────────────────────────────────────
        DB::table('fgw_racks')->insert([
            ['id' => 1, 'rack_code' => 'RAK-A01', 'rack_name' => 'Rak A01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'rack_code' => 'RAK-A02', 'rack_name' => 'Rak A02', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'rack_code' => 'RAK-B01', 'rack_name' => 'Rak B01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'rack_code' => 'RAK-B02', 'rack_name' => 'Rak B02', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Packing Units ────────────────────────────────────────────────────
        // SPK-1 → item 1 → 40 dus
        // SPK-2 → item 2 → 25 dus
        // SPK-3 → item 3 → 20 dus
        $packingUnits = [];
        $baseRunning  = 10004395;

        $spkGroups = [
            ['spk_id' => 1, 'item_id' => 1, 'count' => 40],
            ['spk_id' => 2, 'item_id' => 2, 'count' => 25],
            ['spk_id' => 3, 'item_id' => 3, 'count' => 20],
        ];

        // foreach ($spkGroups as $group) {
        //     for ($i = 1; $i <= $group['count']; $i++) {
        //         $baseRunning++;
        //         $barcode = (string) ($baseRunning + 270005000);

        //         $packingUnits[] = [
        //             'production_order_id' => $group['spk_id'],
        //             'item_id'             => $group['item_id'],
        //             'box_number'          => 'BOX 005-' . $baseRunning,
        //             'barcode'             => $barcode,
        //             'print_batch_id'      => 'PB-DUMMY-2026',
        //             'qty'                 => 1000,
        //             'uom'                 => 'PCS',
        //             'printed_at'          => now(),
        //             'printed_by'          => 1,
        //             'status'              => 'PRINTED',
        //             'fgw_rack_id'         => null,
        //             'created_at'          => now(),
        //             'updated_at'          => now(),
        //         ];
        //     }
        // }

        // DB::table('packing_units')->insert($packingUnits);

        // ── Trolley ──────────────────────────────────────────────────────────
        // DB::table('trolleys')->insert([
        //     [
        //         'id'              => 1,
        //         'trolley_code'    => 'TRL-20260220-0001',
        //         'barcode'         => 'TR202602200001',
        //         'capacity'        => 20,
        //         'status'          => 'OPEN',
        //         'fgw_rack_id'     => null,
        //         'received_fgw_at' => null,
        //         'received_fgw_by' => null,
        //         'created_at'      => now(),
        //         'updated_at'      => now(),
        //     ],
        // ]);

        // delivery_orders + delivery_order_items di-seed oleh SalesOrderSeeder
    }
}
