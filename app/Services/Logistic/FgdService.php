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
                (SELECT COUNT(*) FROM trolleys WHERE status = 'RECEIVED_FGW' AND DATE(received_fgw_at) = CURDATE()) as received_today
        ");

        return $result[0];
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

    public function completeFgwReceiving(int $trolleyId, int $rackId, int $userId): void
    {
        $rack = DB::select("
            SELECT id
            FROM fgw_racks
            WHERE id = ?
            AND is_active = 1
            LIMIT 1
        ", [$rackId]);

        if (!$rack) {
            throw new \Exception('RAK tidak valid atau tidak aktif.');
        }

        $trolley = DB::select("
            SELECT *
            FROM trolleys
            WHERE id = ?
            LIMIT 1
        ", [$trolleyId]);

        if (!$trolley) {
            throw new \Exception('Troli tidak ditemukan.');
        }

        $trolley = $trolley[0];

        if ($trolley->status !== 'SENT_FGW') {
            throw new \Exception('Troli tidak dalam status SENT_FGW.');
        }

        DB::beginTransaction();

        try {
            DB::update("
                UPDATE trolleys
                SET
                    status = 'RECEIVED_FGW',
                    fgw_rack_id = ?,
                    received_fgw_at = NOW(),
                    received_fgw_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $rackId,
                $userId,
                $trolleyId,
            ]);

            DB::update("
                UPDATE packing_units
                SET
                    status = 'RECEIVED_FGW',
                    updated_at = NOW()
                WHERE id IN (
                    SELECT packing_unit_id
                    FROM trolley_items
                    WHERE trolley_id = ?
                )
            ", [$trolleyId]);

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
                'Troli diterima dan tervalidasi FGW. Rack ID: ' . $rackId,
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
                i.item_name
            FROM trolley_items ti
            INNER JOIN packing_units pu ON ti.packing_unit_id = pu.id
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE ti.trolley_id = ?
            ORDER BY ti.id ASC
        ", [$trolleyId]);

        return [
            'trolley' => $trolley[0],
            'items' => $items,
        ];
    }
}
