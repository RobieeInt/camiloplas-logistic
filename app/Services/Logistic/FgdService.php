<?php

namespace App\Services\Logistic;

use Illuminate\Support\Facades\DB;

class FgdService
{
    public function summary(): object
    {
        $result = DB::select("
            SELECT
                (SELECT COUNT(*) FROM trolleys WHERE status = 'SENT_FGW') as waiting_validation,
                (SELECT COUNT(*) FROM trolleys WHERE status = 'RECEIVED_FGW' AND DATE(received_fgw_at) = CURDATE()) as received_today,
                (SELECT COUNT(*) FROM packing_units WHERE status = 'RECEIVED_FGW') as total_dus_fgw
        ");

        return $result[0];
    }

    public function dusPerRack(): array
    {
        return DB::select("
            SELECT
                r.id,
                r.rack_code,
                r.rack_name,
                COUNT(pu.id) as total_dus
            FROM fgw_racks r
            LEFT JOIN packing_units pu
                ON pu.fgw_rack_id = r.id
                AND pu.status = 'RECEIVED_FGW'
            WHERE r.is_active = 1
            GROUP BY r.id, r.rack_code, r.rack_name
            ORDER BY r.rack_code ASC
        ");
    }

    public function stockByItem(): array
    {
        return DB::select("
            SELECT
                i.id          as item_id,
                i.item_code,
                i.item_name,
                COALESCE(r.rack_code, '—') as rack_code,
                COALESCE(r.rack_name,  '—') as rack_name,
                COUNT(pu.id)  as total_dus,
                SUM(pu.qty)   as total_pcs,
                MIN(pu.qty)   as qty_per_box
            FROM packing_units pu
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN fgw_racks r ON pu.fgw_rack_id = r.id
            WHERE pu.status = 'RECEIVED_FGW'
            GROUP BY i.id, i.item_code, i.item_name, r.id, r.rack_code, r.rack_name
            ORDER BY i.item_code ASC, r.rack_code ASC
        ");
    }

    public function getActiveRacks(): array
    {
        return DB::select("
            SELECT
                id,
                rack_code,
                rack_name
            FROM fgw_racks
            WHERE is_active = 1
            ORDER BY rack_code ASC
        ");
    }

    public function getTrolleyForFgwValidation(string $barcode): array
    {
        $trolley = DB::select("
            SELECT *
            FROM trolleys
            WHERE barcode = ?
            LIMIT 1
        ", [$barcode]);

        if (!$trolley) {
            throw new \Exception('Troli tidak ditemukan.');
        }

        $trolley = $trolley[0];

        if ($trolley->status !== 'SENT_FGW') {
            throw new \Exception('Troli belum dikirim dari TWH atau sudah diterima FGW.');
        }

        $items = DB::select("
            SELECT
                pu.id,
                pu.barcode,
                pu.box_number,
                pu.status,
                pu.qty,
                pu.uom,
                po.spk_number,
                po.production_date,
                i.item_name,
                i.item_code
            FROM trolley_items ti
            INNER JOIN packing_units pu ON ti.packing_unit_id = pu.id
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE ti.trolley_id = ?
            ORDER BY pu.status ASC
        ", [$trolley->id]);

        if (count($items) === 0) {
            throw new \Exception('Troli ini tidak punya isi dus.');
        }

        return [
            'trolley' => $trolley,
            'items' => $items,
        ];
    }

    public function validateDusInTrolley(int $trolleyId, string $packingBarcode): object
    {
        $result = DB::select("
            SELECT
                pu.id,
                pu.barcode,
                pu.box_number
            FROM trolley_items ti
            INNER JOIN packing_units pu ON ti.packing_unit_id = pu.id
            WHERE ti.trolley_id = ?
            AND pu.barcode = ?
            LIMIT 1
        ", [
            $trolleyId,
            $packingBarcode,
        ]);

        if (!$result) {
            throw new \Exception('Dus tidak terdaftar di troli ini.');
        }

        return $result[0];
    }

    /**
     * @param  array<int, int>  $packingRackMap  [packing_unit_id => rack_id]
     */
    public function completeFgwReceiving(int $trolleyId, array $packingRackMap, int $userId): void
    {
        if (empty($packingRackMap)) {
            throw new \Exception('Tidak ada dus yang tervalidasi.');
        }

        // Validasi semua rack aktif sekaligus
        // array_values() wajib setelah array_unique() agar key sequential untuk PDO binding
        $rackIds = array_values(array_unique(array_values($packingRackMap)));
        $placeholders = implode(',', array_fill(0, count($rackIds), '?'));
        $validRacks = DB::select(
            "SELECT id FROM fgw_racks WHERE id IN ($placeholders) AND is_active = 1",
            $rackIds
        );

        if (count($validRacks) !== count($rackIds)) {
            throw new \Exception('Satu atau lebih RAK tidak valid atau tidak aktif.');
        }

        $trolley = DB::select("SELECT * FROM trolleys WHERE id = ? LIMIT 1", [$trolleyId]);

        if (!$trolley) {
            throw new \Exception('Troli tidak ditemukan.');
        }

        $trolley = $trolley[0];

        if ($trolley->status !== 'SENT_FGW') {
            throw new \Exception('Troli tidak dalam status SENT_FGW.');
        }

        DB::beginTransaction();

        try {
            // Update trolley — rack tidak disimpan di level troli lagi (partial rack)
            DB::update("
                UPDATE trolleys
                SET
                    status = 'RECEIVED_FGW',
                    fgw_rack_id = NULL,
                    received_fgw_at = NOW(),
                    received_fgw_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$userId, $trolleyId]);

            // Update setiap packing_unit dengan rack-nya masing-masing
            foreach ($packingRackMap as $packingUnitId => $rackId) {
                DB::update("
                    UPDATE packing_units
                    SET
                        status = 'RECEIVED_FGW',
                        fgw_rack_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ", [$rackId, $packingUnitId]);
            }

            $totalDus = count($packingRackMap);
            $totalRak = count($rackIds);

            DB::insert("
                INSERT INTO trolley_histories (
                    trolley_id,
                    status,
                    notes,
                    created_by,
                    created_at,
                    updated_at
                )
                VALUES (?, 'RECEIVED_FGW', ?, ?, NOW(), NOW())
            ", [
                $trolleyId,
                "Troli diterima FGW. {$totalDus} dus tervalidasi ke {$totalRak} rak.",
                $userId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recentReceivedTrolleys(): array
    {
        return DB::select("
            SELECT
                t.id,
                t.trolley_code,
                t.barcode,
                t.status,
                t.received_fgw_at,
                u.name as received_by_name,
                r.rack_code,
                r.rack_name,
                COUNT(ti.id) as total_items
            FROM trolleys t
            LEFT JOIN users u ON t.received_fgw_by = u.id
            LEFT JOIN fgw_racks r ON t.fgw_rack_id = r.id
            LEFT JOIN trolley_items ti ON t.id = ti.trolley_id
            WHERE t.status = 'RECEIVED_FGW'
            GROUP BY
                t.id,
                t.trolley_code,
                t.barcode,
                t.status,
                t.received_fgw_at,
                u.name,
                r.rack_code,
                r.rack_name
            ORDER BY t.received_fgw_at DESC
            LIMIT 30
        ");
    }

    public function getReceivedTrolleyDetail(int $trolleyId): array
    {
        $trolley = DB::select("
            SELECT
                t.id,
                t.trolley_code,
                t.barcode,
                t.status,
                t.received_fgw_at,
                u.name as received_by_name,
                r.rack_code,
                r.rack_name,
                COUNT(ti.id) as total_items
            FROM trolleys t
            LEFT JOIN users u ON t.received_fgw_by = u.id
            LEFT JOIN fgw_racks r ON t.fgw_rack_id = r.id
            LEFT JOIN trolley_items ti ON t.id = ti.trolley_id
            WHERE t.id = ?
            AND t.status = 'RECEIVED_FGW'
            GROUP BY
                t.id,
                t.trolley_code,
                t.barcode,
                t.status,
                t.received_fgw_at,
                u.name,
                r.rack_code,
                r.rack_name
            LIMIT 1
        ", [$trolleyId]);

        if (!$trolley) {
            throw new \Exception('Data troli FGW tidak ditemukan.');
        }

        $items = DB::select("
            SELECT
                pu.id,
                pu.barcode,
                pu.box_number,
                pu.qty,
                pu.uom,
                pu.status,
                po.spk_number,
                po.production_date,
                i.item_code,
                i.item_name,
                r.rack_code,
                r.rack_name
            FROM trolley_items ti
            INNER JOIN packing_units pu ON ti.packing_unit_id = pu.id
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN fgw_racks r ON pu.fgw_rack_id = r.id
            WHERE ti.trolley_id = ?
            ORDER BY r.rack_code ASC, pu.box_number ASC
        ", [$trolleyId]);

        return [
            'trolley' => $trolley[0],
            'items' => $items,
        ];
    }
}
