<?php

namespace App\Services\Logistic;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class TemporaryWarehouseService
{
    public function summary(): object
    {
        $result = DB::select("
            SELECT
                (SELECT COUNT(*) FROM packing_units WHERE DATE(created_at) = CURDATE())     as total_printed_today,
                (SELECT COUNT(*) FROM packing_units WHERE status = 'PRINTED')               as total_ready_scan,
                (SELECT COUNT(*) FROM packing_units WHERE status = 'TW_SCANNED')            as total_tw_scanned,
                (SELECT COUNT(*) FROM trolleys WHERE status = 'OPEN')                       as total_open_trolley,
                (SELECT COUNT(*) FROM trolleys WHERE status = 'COMPLETE')                   as total_complete_trolley,
                (SELECT COUNT(*) FROM trolleys WHERE status = 'SENT_FGW')                   as total_sent_fgw
        ");

        return $result[0];
    }

    public function getItems(): array
    {
        return DB::select("
            SELECT id, item_code, item_name, uom
            FROM items
            ORDER BY item_code ASC
        ");
    }

    public function getProductionOrders(): array
    {
        return DB::select("
            SELECT
                po.id,
                po.spk_number,
                po.production_date,
                po.planned_qty,
                po.status,
                i.item_code,
                i.item_name,
                i.uom,
                so.so_number,
                so.customer_name as so_customer
            FROM production_orders po
            INNER JOIN items i ON po.item_id = i.id
            LEFT JOIN sales_orders so ON po.sales_order_id = so.id
            ORDER BY po.production_date DESC, po.id DESC
        ");
    }

    public function getPackingUnits(string $search = '', int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $offset = ($page - 1) * $perPage;

        $searchQuery = '';
        $bindings = [];

        if (!empty($search)) {
            $searchQuery = "
                AND (
                    pu.barcode LIKE ?
                    OR pu.box_number LIKE ?
                    OR po.spk_number LIKE ?
                    OR i.item_name LIKE ?
                    OR i.item_code LIKE ?
                )
            ";

            $bindings = [
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
                "%{$search}%",
            ];
        }

        $count = DB::select("
            SELECT COUNT(*) as total
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE 1=1
            {$searchQuery}
        ", $bindings);

        $total = $count[0]->total ?? 0;

        $data = DB::select("
            SELECT
                pu.id,
                pu.box_number,
                pu.barcode,
                pu.qty,
                pu.uom,
                pu.printed_at,
                pu.prod_scanned_at,
                pu.status,
                po.spk_number,
                po.production_date,
                i.item_code,
                i.item_name,
                u_print.name  as printed_by_name,
                u_scan.name   as prod_scanned_by_name
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN users u_print ON pu.printed_by = u_print.id
            LEFT JOIN users u_scan  ON pu.prod_scanned_by = u_scan.id
            WHERE 1=1
            {$searchQuery}
            ORDER BY pu.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $bindings);

        return new LengthAwarePaginator($data, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function getOpenTrolleys(): array
    {
        return DB::select("
            SELECT
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.item_id,
                i.item_code,
                i.item_name,
                COUNT(ti.id) as total_items
            FROM trolleys t
            LEFT JOIN items i ON t.item_id = i.id
            LEFT JOIN trolley_items ti ON t.id = ti.trolley_id
            WHERE t.status IN ('OPEN', 'COMPLETE')
            GROUP BY
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.item_id,
                i.item_code,
                i.item_name
            ORDER BY t.id DESC
        ");
    }

    public function printBarcode(int $productionOrderId, int $totalBox, int $qtyPerBox, int $userId): string
    {
        $poResult = DB::select("
            SELECT
                po.id,
                po.item_id,
                po.production_date,
                i.uom
            FROM production_orders po
            INNER JOIN items i ON po.item_id = i.id
            WHERE po.id = ?
            LIMIT 1
        ", [$productionOrderId]);

        if (!$poResult) {
            throw new \Exception('SPK tidak ditemukan.');
        }

        $po = $poResult[0];
        $batchId = 'PB-' . now()->format('YmdHis') . '-' . $userId;

        DB::beginTransaction();

        try {
            for ($i = 1; $i <= $totalBox; $i++) {
                $running = $this->nextPackingRunningNumber();

                DB::insert("
                    INSERT INTO packing_units (
                        production_order_id,
                        item_id,
                        box_number,
                        barcode,
                        print_batch_id,
                        qty,
                        uom,
                        printed_at,
                        printed_by,
                        status,
                        created_at,
                        updated_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'PRINTED', NOW(), NOW())
                ", [
                    $productionOrderId,
                    $po->item_id,
                    'BOX ' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-' . $running,
                    (string) $running,
                    $batchId,
                    $qtyPerBox,
                    $po->uom ?? 'PCS',
                    $userId,
                ]);
            }

            DB::commit();

            return $batchId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function resolveBarcode(string $raw): string
    {
        $raw = trim($raw);
        // Format baru: "ITEM-CODE,12345678" → ambil bagian setelah koma terakhir
        if (str_contains($raw, ',')) {
            $parts = explode(',', $raw);
            return trim(end($parts));
        }
        return $raw;
    }

    public function scanProdBarcode(string $barcode, int $userId): object
    {
        $barcode = $this->resolveBarcode($barcode);

        $packing = DB::select("
            SELECT pu.*, i.item_name, i.item_code
            FROM packing_units pu
            INNER JOIN items i ON pu.item_id = i.id
            WHERE pu.barcode = ?
            LIMIT 1
        ", [$barcode]);

        if (!$packing) {
            throw new \Exception('Barcode tidak ditemukan.');
        }

        $packing = $packing[0];

        if ($packing->status !== 'PRINTED') {
            if ($packing->status === 'TW_SCANNED') {
                throw new \Exception('Dus ini sudah pernah di-scan di TW. Status: TW_SCANNED.');
            }
            throw new \Exception('Dus tidak bisa di-scan. Status sekarang: ' . $packing->status);
        }

        DB::update("
            UPDATE packing_units
            SET
                status          = 'TW_SCANNED',
                prod_scanned_by = ?,
                prod_scanned_at = NOW(),
                updated_at      = NOW()
            WHERE id = ?
        ", [$userId, $packing->id]);

        return (object) [
            'barcode'    => $packing->barcode,
            'box_number' => $packing->box_number,
            'item_name'  => $packing->item_name,
            'item_code'  => $packing->item_code,
        ];
    }

    public function getPrintLabels(string $batchId): array
    {
        return DB::select("
            SELECT
                pu.id,
                pu.box_number,
                pu.barcode,
                pu.qty,
                pu.uom,
                pu.printed_at,
                po.spk_number,
                po.production_date,
                i.item_code,
                i.item_name
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE pu.print_batch_id = ?
            ORDER BY pu.id ASC
        ", [$batchId]);
    }

    public function getPrintLabelByPackingUnitId(int $packingUnitId): array
    {
        return DB::select("
            SELECT
                pu.id,
                pu.box_number,
                pu.barcode,
                pu.qty,
                pu.uom,
                pu.printed_at,
                po.spk_number,
                po.production_date,
                i.item_code,
                i.item_name
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE pu.id = ?
            LIMIT 1
        ", [$packingUnitId]);
    }

    public function getTrolleyQrLabel(int $trolleyId): ?object
    {
        $result = DB::select("
            SELECT
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.created_at,
                COUNT(ti.id) as total_items
            FROM trolleys t
            LEFT JOIN trolley_items ti ON t.id = ti.trolley_id
            WHERE t.id = ?
            GROUP BY
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.created_at
            LIMIT 1
        ", [$trolleyId]);

        return $result[0] ?? null;
    }

    private function nextPackingRunningNumber(): int
    {
        $result = DB::select("
            SELECT MAX(CAST(barcode AS UNSIGNED)) as last_number
            FROM packing_units
            WHERE barcode REGEXP '^[0-9]+$'
        ");

        $last = $result[0]->last_number ?? 12700094395;

        return (int) $last + 1;
    }

    public function createTrolley(int $userId, $capacity, ?int $itemId = null): int
    {
        if ($itemId !== null) {
            $item = DB::select("SELECT id FROM items WHERE id = ? LIMIT 1", [$itemId]);
            if (!$item) {
                throw new \Exception('Item tidak ditemukan.');
            }
        }

        $today = now()->format('Ymd');

        $result = DB::select("
            SELECT COUNT(*) + 1 as next_number
            FROM trolleys
            WHERE DATE(created_at) = CURDATE()
        ");

        $next = str_pad((string) ($result[0]->next_number ?? 1), 4, '0', STR_PAD_LEFT);

        $trolleyCode = "TRL-{$today}-{$next}";
        $barcode = "TR{$today}{$next}";

        DB::beginTransaction();

        try {
            DB::insert("
                INSERT INTO trolleys (
                    item_id,
                    trolley_code,
                    barcode,
                    capacity,
                    status,
                    created_by,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, 'OPEN', ?, NOW(), NOW())
            ", [
                $itemId,
                $trolleyCode,
                $barcode,
                $capacity,
                $userId,
            ]);

            $trolleyId = (int) DB::getPdo()->lastInsertId();

            DB::insert("
                INSERT INTO trolley_histories (
                    trolley_id,
                    status,
                    notes,
                    created_by,
                    created_at,
                    updated_at
                )
                VALUES (?, 'OPEN', 'Troli dibuat dari Temporary Warehouse QC', ?, NOW(), NOW())
            ", [
                $trolleyId,
                $userId,
            ]);

            DB::commit();

            return $trolleyId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function scanDusToSelectedTrolley(string $packingBarcode, int $trolleyId, int $userId): object
    {
        $packingBarcode = $this->resolveBarcode($packingBarcode);

        $packing = DB::select("
            SELECT pu.*, i.item_name as packing_item_name
            FROM packing_units pu
            INNER JOIN items i ON pu.item_id = i.id
            WHERE pu.barcode = ?
            LIMIT 1
        ", [$packingBarcode]);

        if (!$packing) {
            throw new \Exception('Barcode dus tidak ditemukan.');
        }

        $packing = $packing[0];

        if ($packing->status !== 'TW_SCANNED') {
            if ($packing->status === 'PRINTED') {
                throw new \Exception('Dus belum di-scan di Temporary Warehouse. Scan dulu di halaman TW sebelum masuk troli.');
            }
            throw new \Exception('Dus tidak bisa dimasukkan ke troli. Status sekarang: ' . $packing->status);
        }

        $trolley = DB::select("
            SELECT t.*, i.item_name as trolley_item_name
            FROM trolleys t
            LEFT JOIN items i ON t.item_id = i.id
            WHERE t.id = ?
            LIMIT 1
        ", [$trolleyId]);

        if (!$trolley) {
            throw new \Exception('Troli tidak ditemukan.');
        }

        $trolley = $trolley[0];

        if ($trolley->status !== 'OPEN') {
            throw new \Exception('Troli tidak dalam status OPEN.');
        }

        if ($trolley->item_id !== null && (int) $packing->item_id !== (int) $trolley->item_id) {
            throw new \Exception(
                'Item tidak cocok! Dus ini adalah ' . $packing->packing_item_name .
                ', troli ini khusus untuk ' . $trolley->trolley_item_name . '.'
            );
        }

        $count = DB::select("
            SELECT COUNT(*) as total
            FROM trolley_items
            WHERE trolley_id = ?
        ", [$trolley->id]);

        $total = (int) ($count[0]->total ?? 0);

        if (
            !is_null($trolley->capacity) &&
            $total >= $trolley->capacity
        ) {
            throw new \Exception('Troli sudah penuh.');
        }

        DB::beginTransaction();

        try {
            DB::insert("
                INSERT INTO trolley_items (
                    trolley_id,
                    packing_unit_id,
                    scanned_at,
                    scanned_by,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, NOW(), ?, NOW(), NOW())
            ", [
                $trolley->id,
                $packing->id,
                $userId,
            ]);

            DB::update("
                UPDATE packing_units
                SET
                    status = 'SCANNED_TO_TROLLEY',
                    updated_at = NOW()
                WHERE id = ?
            ", [$packing->id]);

            $newTotal = $total + 1;
            $status = 'OPEN';

            if (
                    !is_null($trolley->capacity) &&
                    $newTotal >= $trolley->capacity

                ) {
                $status = 'COMPLETE';

                DB::update("
                    UPDATE trolleys
                    SET
                        status = 'COMPLETE',
                        updated_at = NOW()
                    WHERE id = ?
                ", [$trolley->id]);

                DB::insert("
                    INSERT INTO trolley_histories (
                        trolley_id,
                        status,
                        notes,
                        created_by,
                        created_at,
                        updated_at
                    )
                    VALUES (?, 'COMPLETE', 'Troli lengkap sesuai capacity', ?, NOW(), NOW())
                ", [
                    $trolley->id,
                    $userId,
                ]);
            }

            DB::commit();

            return (object) [
                'packing_barcode' => $packingBarcode,
                'trolley_id' => $trolley->id,
                'trolley_code' => $trolley->trolley_code,
                'trolley_barcode' => $trolley->barcode,
                'total_items' => $newTotal,
                'capacity' => $trolley->capacity,
                'status' => $status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getTrolleyDetail(int $trolleyId): array
    {
        $trolley = DB::select("
            SELECT
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.created_at,
                COUNT(ti.id) as total_items
            FROM trolleys t
            LEFT JOIN trolley_items ti ON t.id = ti.trolley_id
            WHERE t.id = ?
            GROUP BY
                t.id,
                t.trolley_code,
                t.barcode,
                t.capacity,
                t.status,
                t.created_at
            LIMIT 1
        ", [$trolleyId]);

        if (!$trolley) {
            throw new \Exception('Troli tidak ditemukan.');
        }

        $items = DB::select("
            SELECT
                pu.id as packing_unit_id,
                pu.box_number,
                pu.barcode,
                pu.qty,
                pu.uom,
                pu.status,
                ti.scanned_at,
                ti.scanned_by,
                users.name as scanned_by_name,
                po.spk_number,
                po.production_date,
                i.item_code,
                i.item_name
            FROM trolley_items ti
            INNER JOIN packing_units pu ON ti.packing_unit_id = pu.id
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN users ON ti.scanned_by = users.id
            WHERE ti.trolley_id = ?
            ORDER BY ti.id DESC
        ", [$trolleyId]);

        return [
            'trolley' => $trolley[0],
            'items' => $items,
        ];
    }

    public function removeDusFromTrolley(int $trolleyId, int $packingUnitId, int $userId): void
    {
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

        if ($trolley->status !== 'OPEN') {
            throw new \Exception('Dus hanya bisa dikeluarkan kalau troli masih OPEN.');
        }

        $item = DB::select("
            SELECT *
            FROM trolley_items
            WHERE trolley_id = ?
            AND packing_unit_id = ?
            LIMIT 1
        ", [$trolleyId, $packingUnitId]);

        if (!$item) {
            throw new \Exception('Dus tidak ditemukan di troli ini.');
        }

        DB::beginTransaction();

        try {
            DB::delete("
                DELETE FROM trolley_items
                WHERE trolley_id = ?
                AND packing_unit_id = ?
            ", [$trolleyId, $packingUnitId]);

            DB::update("
                UPDATE packing_units
                SET
                    status = 'TW_SCANNED',
                    updated_at = NOW()
                WHERE id = ?
            ", [$packingUnitId]);

            DB::insert("
                INSERT INTO trolley_histories (
                    trolley_id,
                    status,
                    notes,
                    created_by,
                    created_at,
                    updated_at
                )
                VALUES (?, 'REMOVE_DUS', ?, ?, NOW(), NOW())
            ", [
                $trolleyId,
                'Dus packing_unit_id ' . $packingUnitId . ' dikeluarkan dari troli',
                $userId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function forceCompleteTrolley(int $trolleyId, int $userId): void
    {
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

        if ($trolley->status !== 'OPEN') {
            throw new \Exception('Hanya troli OPEN yang bisa di-force complete.');
        }

        $count = DB::select("
            SELECT COUNT(*) as total
            FROM trolley_items
            WHERE trolley_id = ?
        ", [$trolleyId]);

        $total = (int) ($count[0]->total ?? 0);

        if ($total <= 0) {
            throw new \Exception('Troli kosong tidak bisa di-force complete.');
        }

        DB::beginTransaction();

        try {
            DB::update("
                UPDATE trolleys
                SET
                    status = 'COMPLETE',
                    updated_at = NOW()
                WHERE id = ?
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
                VALUES (?, 'FORCE_COMPLETE', ?, ?, NOW(), NOW())
            ", [
                $trolleyId,
                'Troli di-force complete dengan isi ' . $total . '/' . $trolley->capacity . ' dus',
                $userId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sendToFgw(int $trolleyId, int $userId): void
    {
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

        if ($trolley->status !== 'COMPLETE') {
            throw new \Exception('Troli belum COMPLETE, belum bisa dikirim ke FGW.');
        }

        DB::beginTransaction();

        try {
            DB::update("
                UPDATE trolleys
                SET
                    status = 'SENT_FGW',
                    updated_at = NOW()
                WHERE id = ?
            ", [$trolleyId]);

            DB::update("
                UPDATE packing_units
                SET
                    status = 'SENT_FGW',
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
                VALUES (?, 'SENT_FGW', 'Troli dikirim ke Finish Goods Warehouse', ?, NOW(), NOW())
            ", [
                $trolleyId,
                $userId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
