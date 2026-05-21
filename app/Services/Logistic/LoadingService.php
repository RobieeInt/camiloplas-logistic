<?php

namespace App\Services\Logistic;

use Illuminate\Support\Facades\DB;

class LoadingService
{
    public function summary(): object
    {
        $result = DB::select("
            SELECT
                (SELECT COUNT(*) FROM delivery_orders WHERE status = 'READY') as ready_orders,
                (SELECT COUNT(*) FROM delivery_orders WHERE status = 'LOADING') as loading_orders,
                (SELECT COUNT(*) FROM delivery_orders WHERE status = 'LOADED') as loaded_orders,
                (SELECT COUNT(*) FROM packing_units WHERE status = 'RECEIVED_FGW') as stock_fgw
        ");

        return $result[0];
    }

    public function readyOrders(): array
    {
        return DB::select("
            SELECT
                id,
                so_number,
                do_number,
                customer_name,
                truck_number,
                status,
                created_at
            FROM delivery_orders
            WHERE status IN ('READY', 'LOADING')
            ORDER BY id DESC
        ");
    }

    public function getOrderDetail(int $deliveryOrderId): array
    {
        $order = DB::select("
            SELECT *
            FROM delivery_orders
            WHERE id = ?
            LIMIT 1
        ", [$deliveryOrderId]);

        if (!$order) {
            throw new \Exception('DO tidak ditemukan.');
        }

        $items = DB::select("
            SELECT
                doi.id,
                doi.item_id,
                doi.required_boxes,
                doi.loaded_boxes,
                i.item_code,
                i.item_name,
                i.uom
            FROM delivery_order_items doi
            INNER JOIN items i ON doi.item_id = i.id
            WHERE doi.delivery_order_id = ?
            ORDER BY doi.id ASC
        ", [$deliveryOrderId]);

        return [
            'order' => $order[0],
            'items' => $items,
        ];
    }

    public function loadedItems(int $deliveryOrderId): array
    {
        return DB::select("
            SELECT
                li.id,
                li.loaded_at,
                pu.id as packing_unit_id,
                pu.barcode,
                pu.box_number,
                pu.qty,
                pu.uom,
                i.item_code,
                i.item_name,
                t.trolley_code,
                r.rack_code,
                u.name as loaded_by_name
            FROM loading_items li
            INNER JOIN packing_units pu ON li.packing_unit_id = pu.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN trolleys t ON li.trolley_id = t.id
            LEFT JOIN fgw_racks r ON t.fgw_rack_id = r.id
            LEFT JOIN users u ON li.loaded_by = u.id
            WHERE li.delivery_order_id = ?
            ORDER BY li.id DESC
        ", [$deliveryOrderId]);
    }

    public function scanDusToTruck(int $deliveryOrderId, string $packingBarcode, int $userId): object
    {
        $order = DB::select("
            SELECT *
            FROM delivery_orders
            WHERE id = ?
            LIMIT 1
        ", [$deliveryOrderId]);

        if (!$order) {
            throw new \Exception('DO tidak ditemukan.');
        }

        $order = $order[0];

        if (!in_array($order->status, ['READY', 'LOADING'])) {
            throw new \Exception('DO sudah tidak bisa loading. Status: ' . $order->status);
        }

        $packing = DB::select("
            SELECT
                pu.*,
                ti.trolley_id,
                t.status as trolley_status,
                t.fgw_rack_id
            FROM packing_units pu
            LEFT JOIN trolley_items ti ON pu.id = ti.packing_unit_id
            LEFT JOIN trolleys t ON ti.trolley_id = t.id
            WHERE pu.barcode = ?
            LIMIT 1
        ", [$packingBarcode]);

        if (!$packing) {
            throw new \Exception('Barcode dus tidak ditemukan.');
        }

        $packing = $packing[0];

        if ($packing->status !== 'RECEIVED_FGW') {
            throw new \Exception('Dus belum available di FGW. Status sekarang: ' . $packing->status);
        }

        if ($packing->trolley_status !== 'RECEIVED_FGW') {
            throw new \Exception('Troli dus ini belum RECEIVED_FGW.');
        }

        $doItem = DB::select("
            SELECT *
            FROM delivery_order_items
            WHERE delivery_order_id = ?
            AND item_id = ?
            LIMIT 1
        ", [
            $deliveryOrderId,
            $packing->item_id,
        ]);

        if (!$doItem) {
            throw new \Exception('Item dus ini tidak ada di DO.');
        }

        $doItem = $doItem[0];

        if ((int) $doItem->loaded_boxes >= (int) $doItem->required_boxes) {
            throw new \Exception('Qty item ini sudah lengkap sesuai DO.');
        }

        $alreadyLoaded = DB::select("
            SELECT id
            FROM loading_items
            WHERE packing_unit_id = ?
            LIMIT 1
        ", [$packing->id]);

        if ($alreadyLoaded) {
            throw new \Exception('Dus ini sudah pernah masuk loading.');
        }

        DB::beginTransaction();

        try {
            DB::insert("
                INSERT INTO loading_items (
                    delivery_order_id,
                    packing_unit_id,
                    trolley_id,
                    loaded_at,
                    loaded_by,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, NOW(), ?, NOW(), NOW())
            ", [
                $deliveryOrderId,
                $packing->id,
                $packing->trolley_id,
                $userId,
            ]);

            DB::update("
                UPDATE packing_units
                SET
                    status = 'LOADED',
                    updated_at = NOW()
                WHERE id = ?
            ", [$packing->id]);

            DB::update("
                UPDATE delivery_order_items
                SET
                    loaded_boxes = loaded_boxes + 1,
                    updated_at = NOW()
                WHERE id = ?
            ", [$doItem->id]);

            DB::update("
                UPDATE delivery_orders
                SET
                    status = 'LOADING',
                    updated_at = NOW()
                WHERE id = ?
                AND status = 'READY'
            ", [$deliveryOrderId]);

            DB::commit();

            return (object) [
                'barcode' => $packing->barcode,
                'box_number' => $packing->box_number,
                'item_id' => $packing->item_id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function validateCompleteness(int $deliveryOrderId): object
    {
        $result = DB::select("
            SELECT
                SUM(required_boxes) as total_required,
                SUM(loaded_boxes) as total_loaded,
                SUM(CASE WHEN loaded_boxes < required_boxes THEN 1 ELSE 0 END) as incomplete_rows
            FROM delivery_order_items
            WHERE delivery_order_id = ?
        ", [$deliveryOrderId]);

        return $result[0];
    }

    public function completeLoading(int $deliveryOrderId, int $userId): void
    {
        $validation = $this->validateCompleteness($deliveryOrderId);

        if ((int) $validation->incomplete_rows > 0) {
            throw new \Exception('Loading belum lengkap. Pastikan semua item telah dimuat.');
        }

        DB::beginTransaction();

        try {
            DB::update("
                UPDATE delivery_orders
                SET
                    status = 'LOADED',
                    loaded_at = NOW(),
                    loaded_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $userId,
                $deliveryOrderId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recentLoadedOrders(): array
    {
        return DB::select("
            SELECT
                dox.id,
                dox.so_number,
                dox.do_number,
                dox.customer_name,
                dox.truck_number,
                dox.status,
                dox.loaded_at,
                u.name as loaded_by_name,
                SUM(doi.required_boxes) as total_required,
                SUM(doi.loaded_boxes) as total_loaded
            FROM delivery_orders dox
            INNER JOIN delivery_order_items doi ON dox.id = doi.delivery_order_id
            LEFT JOIN users u ON dox.loaded_by = u.id
            WHERE dox.status = 'LOADED'
            GROUP BY
                dox.id,
                dox.so_number,
                dox.do_number,
                dox.customer_name,
                dox.truck_number,
                dox.status,
                dox.loaded_at,
                u.name
            ORDER BY dox.loaded_at DESC
            LIMIT 20
        ");
    }

    public function getPrintDocumentData(int $deliveryOrderId): array
    {
        $order = DB::select("
            SELECT
                dox.*,
                u.name as loaded_by_name
            FROM delivery_orders dox
            LEFT JOIN users u ON dox.loaded_by = u.id
            WHERE dox.id = ?
            LIMIT 1
        ", [$deliveryOrderId]);

        if (!$order) {
            throw new \Exception('DO tidak ditemukan.');
        }

        $items = DB::select("
            SELECT
                doi.required_boxes,
                doi.loaded_boxes,
                i.item_code,
                i.item_name,
                i.uom
            FROM delivery_order_items doi
            INNER JOIN items i ON doi.item_id = i.id
            WHERE doi.delivery_order_id = ?
            ORDER BY doi.id ASC
        ", [$deliveryOrderId]);

        $loadedItems = DB::select("
            SELECT
                pu.barcode,
                pu.box_number,
                pu.qty,
                pu.uom,
                i.item_code,
                i.item_name,
                t.trolley_code,
                r.rack_code
            FROM loading_items li
            INNER JOIN packing_units pu ON li.packing_unit_id = pu.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN trolleys t ON li.trolley_id = t.id
            LEFT JOIN fgw_racks r ON t.fgw_rack_id = r.id
            WHERE li.delivery_order_id = ?
            ORDER BY li.id ASC
        ", [$deliveryOrderId]);

        return [
            'order' => $order[0],
            'items' => $items,
            'loadedItems' => $loadedItems,
        ];
    }

public function markDoAsPrinted(int $deliveryOrderId): void
{
    DB::update("
        UPDATE delivery_orders
        SET
            do_print_count = COALESCE(do_print_count, 0) + 1,
            do_first_printed_at = COALESCE(do_first_printed_at, NOW()),
            updated_at = NOW()
        WHERE id = ?
    ", [$deliveryOrderId]);
}

public function markSuratJalanAsPrinted(int $deliveryOrderId): void
{
    DB::update("
        UPDATE delivery_orders
        SET
            surat_jalan_print_count = COALESCE(surat_jalan_print_count, 0) + 1,
            surat_jalan_first_printed_at = COALESCE(surat_jalan_first_printed_at, NOW()),
            updated_at = NOW()
        WHERE id = ?
    ", [$deliveryOrderId]);
}
}
