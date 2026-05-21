<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('loading_items')->delete();
        DB::table('delivery_order_items')->delete();
        DB::table('delivery_orders')->delete();
        DB::table('sales_order_details')->delete();
        DB::table('sales_orders')->delete();

        // ── Sales Orders ─────────────────────────────────────────────────────
        DB::table('sales_orders')->insert([
            [
                'id'               => 1,
                'so_number'        => 'SO-2026-0001',
                'customer_name'    => 'PT. Mitra Sukses Abadi',
                'customer_address' => 'Jl. Industri No.12, Bekasi Barat',
                'status'           => 'OPEN',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => 2,
                'so_number'        => 'SO-2026-0002',
                'customer_name'    => 'CV. Bintang Timur',
                'customer_address' => 'Jl. Raya Serpong No.45, Tangerang',
                'status'           => 'OPEN',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => 3,
                'so_number'        => 'SO-2026-0003',
                'customer_name'    => 'PT. Global Nusantara',
                'customer_address' => 'Jl. Gatot Subroto No.88, Jakarta',
                'status'           => 'OPEN',
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);

        // ── SO Details (tiap SO punya kombinasi item beda) ───────────────────
        //
        // SO-1 → item 1 (10OZ Cup) + item 2 (16OZ Cup)
        // SO-2 → item 2 (16OZ Cup) + item 3 (Soup Bowl)
        // SO-3 → item 1 (10OZ Cup) + item 3 (Soup Bowl) + item 4 (Straw)

        DB::table('sales_order_details')->insert([
            // SO-1
            ['sales_order_id' => 1, 'item_id' => 1, 'qty' => 20000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            // ['sales_order_id' => 1, 'item_id' => 2, 'qty' => 15000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            // SO-2
            ['sales_order_id' => 2, 'item_id' => 2, 'qty' => 10000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            // ['sales_order_id' => 2, 'item_id' => 3, 'qty' =>  8000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            // SO-3
            // ['sales_order_id' => 3, 'item_id' => 1, 'qty' => 30000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            // ['sales_order_id' => 3, 'item_id' => 3, 'qty' => 12000, 'uom' => 'PCS', 'notes' => null, 'created_at' => now(), 'updated_at' => now()],
            ['sales_order_id' => 3, 'item_id' => 4, 'qty' => 25000, 'uom' => 'PCS', 'notes' => 'Straw wrapped per 100pcs', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Link production_orders ke SO ─────────────────────────────────────
        DB::table('production_orders')->where('id', 1)->update(['sales_order_id' => 1]); // SPK-1 → SO-1 (item 1)
        DB::table('production_orders')->where('id', 2)->update(['sales_order_id' => 2]); // SPK-2 → SO-2 (item 2)
        DB::table('production_orders')->where('id', 3)->update(['sales_order_id' => 2]); // SPK-3 → SO-2 (item 3)

        // ── Delivery Orders ──────────────────────────────────────────────────
        // DB::table('delivery_orders')->insert([
        //     [
        //         'id'                         => 1,
        //         'sales_order_id'             => 1,
        //         'so_number'                  => 'SO-2026-0001',
        //         'do_number'                  => 'DO-2026-0001',
        //         'customer_name'              => 'PT. Mitra Sukses Abadi',
        //         'truck_number'               => null,
        //         'status'                     => 'READY',
        //         'loaded_at'                  => null,
        //         'loaded_by'                  => null,
        //         'do_print_count'             => 0,
        //         'do_first_printed_at'        => null,
        //         'surat_jalan_print_count'    => 0,
        //         'surat_jalan_first_printed_at' => null,
        //         'created_at'                 => now(),
        //         'updated_at'                 => now(),
        //     ],
        //     [
        //         'id'                         => 2,
        //         'sales_order_id'             => 2,
        //         'so_number'                  => 'SO-2026-0002',
        //         'do_number'                  => 'DO-2026-0002',
        //         'customer_name'              => 'CV. Bintang Timur',
        //         'truck_number'               => null,
        //         'status'                     => 'READY',
        //         'loaded_at'                  => null,
        //         'loaded_by'                  => null,
        //         'do_print_count'             => 0,
        //         'do_first_printed_at'        => null,
        //         'surat_jalan_print_count'    => 0,
        //         'surat_jalan_first_printed_at' => null,
        //         'created_at'                 => now(),
        //         'updated_at'                 => now(),
        //     ],
        // ]);

        // ── Delivery Order Items (sesuai SO detail, beda-beda per DO) ────────
        //
        // DO-1 (SO-1): item 1 (20 box) + item 2 (15 box)
        // DO-2 (SO-2): item 2 (10 box) + item 3 (8 box)

        // DB::table('delivery_order_items')->insert([
        //     // DO-1
        //     ['delivery_order_id' => 1, 'item_id' => 1, 'required_boxes' => 20, 'loaded_boxes' => 0, 'created_at' => now(), 'updated_at' => now()],
        //     ['delivery_order_id' => 1, 'item_id' => 2, 'required_boxes' => 15, 'loaded_boxes' => 0, 'created_at' => now(), 'updated_at' => now()],
        //     // DO-2
        //     ['delivery_order_id' => 2, 'item_id' => 2, 'required_boxes' => 10, 'loaded_boxes' => 0, 'created_at' => now(), 'updated_at' => now()],
        //     ['delivery_order_id' => 2, 'item_id' => 3, 'required_boxes' =>  8, 'loaded_boxes' => 0, 'created_at' => now(), 'updated_at' => now()],
        // ]);
    }
}
