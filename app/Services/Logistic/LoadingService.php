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

    public function getSalesOrders(): array
    {
        return DB::select("
            SELECT
                so.id,
                so.so_number,
                so.customer_name,
                so.customer_address,
                so.status,
                COUNT(sod.id) as total_items
            FROM sales_orders so
            LEFT JOIN sales_order_details sod ON so.id = sod.sales_order_id
            WHERE so.status = 'OPEN'
            GROUP BY so.id, so.so_number, so.customer_name, so.customer_address, so.status
            ORDER BY so.id DESC
        ");
    }

    public function getMultipleSoDetails(array $soIds): array
    {
        if (empty($soIds)) {
            return ['sos' => [], 'details' => []];
        }

        $soIds        = array_values(array_unique(array_map('intval', $soIds)));
        $placeholders = implode(',', array_fill(0, count($soIds), '?'));

        $sos = DB::select("
            SELECT id, so_number, customer_name, customer_address, status
            FROM sales_orders
            WHERE id IN ($placeholders)
            ORDER BY id ASC
        ", $soIds);

        if (empty($sos)) {
            return ['sos' => [], 'details' => []];
        }

        // Merge item by item_id, sum qty across all selected SOs + stok FGW
        $details = DB::select("
            SELECT
                sod.item_id,
                i.item_code,
                i.item_name,
                i.uom,
                SUM(sod.qty) as qty,
                (
                    SELECT COUNT(*)
                    FROM packing_units pu
                    WHERE pu.item_id = sod.item_id
                    AND pu.status = 'RECEIVED_FGW'
                ) as stock_dus,
                (
                    SELECT COALESCE(SUM(pu2.qty), 0)
                    FROM packing_units pu2
                    WHERE pu2.item_id = sod.item_id
                    AND pu2.status = 'RECEIVED_FGW'
                ) as stock_pcs,
                (
                    SELECT pu3.qty
                    FROM packing_units pu3
                    WHERE pu3.item_id = sod.item_id
                    AND pu3.status = 'RECEIVED_FGW'
                    LIMIT 1
                ) as qty_per_box
            FROM sales_order_details sod
            INNER JOIN items i ON sod.item_id = i.id
            WHERE sod.sales_order_id IN ($placeholders)
            GROUP BY sod.item_id, i.item_code, i.item_name, i.uom
            ORDER BY sod.item_id ASC
        ", $soIds);

        // Hitung PCS yang sudah masuk truck dari DO-DO milik SO ini
        $loadedRows = DB::select("
            SELECT
                pu.item_id,
                COALESCE(SUM(pu.qty), 0) as already_loaded_pcs
            FROM loading_items li
            INNER JOIN packing_units pu ON li.packing_unit_id = pu.id
            INNER JOIN delivery_orders dox ON li.delivery_order_id = dox.id
            WHERE dox.sales_order_id IN ($placeholders)
            GROUP BY pu.item_id
        ", $soIds);

        $loadedMap = collect($loadedRows)->keyBy('item_id');

        foreach ($details as $detail) {
            $loaded                   = $loadedMap->get($detail->item_id);
            $detail->already_loaded_pcs = $loaded ? (int) $loaded->already_loaded_pcs : 0;
            $detail->remaining_pcs    = max(0, (int) $detail->qty - $detail->already_loaded_pcs);
        }

        return [
            'sos'     => $sos,
            'details' => $details,
        ];
    }

    public function createDoFromSos(array $soIds, string $doNumber, array $requiredBoxes, int $userId): int
    {
        if (empty($soIds)) {
            throw new \Exception('Pilih minimal 1 SO.');
        }

        $soIds        = array_values(array_unique(array_map('intval', $soIds)));
        $placeholders = implode(',', array_fill(0, count($soIds), '?'));

        $sos = DB::select("
            SELECT id, so_number, customer_name, customer_address, customer_po_number
            FROM sales_orders
            WHERE id IN ($placeholders)
            AND status = 'OPEN'
            ORDER BY id ASC
        ", $soIds);

        if (count($sos) !== count($soIds)) {
            throw new \Exception('Satu atau lebih SO tidak ditemukan atau tidak dalam status OPEN.');
        }

        $doNumber = trim($doNumber);
        if (empty($doNumber)) {
            throw new \Exception('DO Number wajib diisi.');
        }

        $exists = DB::select("SELECT id FROM delivery_orders WHERE do_number = ? LIMIT 1", [$doNumber]);
        if ($exists) {
            throw new \Exception("DO number {$doNumber} sudah digunakan.");
        }

        // Ambil semua item unik dari semua SO yang dipilih
        $soDetails = DB::select("
            SELECT DISTINCT item_id
            FROM sales_order_details
            WHERE sales_order_id IN ($placeholders)
            ORDER BY item_id ASC
        ", $soIds);

        if (empty($soDetails)) {
            throw new \Exception('SO yang dipilih belum memiliki detail item.');
        }

        $soNumbers       = implode(' / ', array_map(fn ($s) => $s->so_number, $sos));
        $customerNames   = array_unique(array_map(fn ($s) => $s->customer_name, $sos));
        $customerName    = implode(' / ', $customerNames);
        $customerAddress = $sos[0]->customer_address ?? '';

        DB::beginTransaction();

        try {
            DB::insert("
                INSERT INTO delivery_orders (
                    sales_order_id,
                    so_number,
                    do_number,
                    customer_name,
                    customer_address,
                    truck_number,
                    driver_name,
                    status,
                    do_print_count,
                    surat_jalan_print_count,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, NULL, NULL, 'READY', 0, 0, NOW(), NOW())
            ", [
                $sos[0]->id,
                $soNumbers,
                $doNumber,
                $customerName,
                $customerAddress,
            ]);

            $doId = (int) DB::getPdo()->lastInsertId();

            foreach ($soDetails as $detail) {
                $itemId = $detail->item_id;
                $boxes  = (int) ($requiredBoxes[$itemId] ?? 0);

                DB::insert("
                    INSERT INTO delivery_order_items (
                        delivery_order_id,
                        item_id,
                        required_boxes,
                        loaded_boxes,
                        created_at,
                        updated_at
                    )
                    VALUES (?, ?, ?, 0, NOW(), NOW())
                ", [$doId, $itemId, $boxes]);
            }

            DB::commit();

            return $doId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getSalesOrderWithDetails(int $soId): array
    {
        $so = DB::select("
            SELECT id, so_number, customer_name, customer_address, status
            FROM sales_orders
            WHERE id = ?
            LIMIT 1
        ", [$soId]);

        if (!$so) {
            throw new \Exception('SO tidak ditemukan.');
        }

        $details = DB::select("
            SELECT
                sod.id,
                sod.item_id,
                sod.qty,
                sod.uom,
                sod.notes,
                i.item_code,
                i.item_name
            FROM sales_order_details sod
            INNER JOIN items i ON sod.item_id = i.id
            WHERE sod.sales_order_id = ?
            ORDER BY sod.id ASC
        ", [$soId]);

        return [
            'so'      => $so[0],
            'details' => $details,
        ];
    }

    public function createDoFromSo(int $soId, string $doNumber, array $requiredBoxes, int $userId): int
    {
        $so = DB::select("SELECT * FROM sales_orders WHERE id = ? LIMIT 1", [$soId]);
        if (!$so) {
            throw new \Exception('SO tidak ditemukan.');
        }
        $so = $so[0];

        $doNumber = trim($doNumber);
        if (empty($doNumber)) {
            throw new \Exception('DO Number wajib diisi.');
        }

        $exists = DB::select("SELECT id FROM delivery_orders WHERE do_number = ? LIMIT 1", [$doNumber]);
        if ($exists) {
            throw new \Exception("DO number {$doNumber} sudah digunakan.");
        }

        // Ambil semua item dari SO — semua item dimasukkan ke DO meski boxes = 0
        $soDetails = DB::select("
            SELECT item_id
            FROM sales_order_details
            WHERE sales_order_id = ?
            ORDER BY id ASC
        ", [$soId]);

        if (empty($soDetails)) {
            throw new \Exception('SO ini belum memiliki detail item. Tambahkan item ke SO terlebih dahulu.');
        }

        DB::beginTransaction();

        try {
            DB::insert("
                INSERT INTO delivery_orders (
                    sales_order_id,
                    so_number,
                    do_number,
                    customer_name,
                    customer_address,
                    truck_number,
                    driver_name,
                    status,
                    do_print_count,
                    surat_jalan_print_count,
                    created_at,
                    updated_at
                )
                VALUES (?, ?, ?, ?, ?, NULL, NULL, 'READY', 0, 0, NOW(), NOW())
            ", [
                $soId,
                $so->so_number,
                $doNumber,
                $so->customer_name,
                $so->customer_address ?? '',
            ]);

            $doId = (int) DB::getPdo()->lastInsertId();

            // Insert semua item dari SO, required_boxes = 0 berarti tanpa target
            foreach ($soDetails as $detail) {
                $itemId  = $detail->item_id;
                $boxes   = (int) ($requiredBoxes[$itemId] ?? 0);

                DB::insert("
                    INSERT INTO delivery_order_items (
                        delivery_order_id,
                        item_id,
                        required_boxes,
                        loaded_boxes,
                        created_at,
                        updated_at
                    )
                    VALUES (?, ?, ?, 0, NOW(), NOW())
                ", [$doId, $itemId, $boxes]);
            }

            DB::commit();

            return $doId;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function nextDoNumber(): string
    {
        $today = now()->format('Ymd');

        $result = DB::select("
            SELECT COUNT(*) + 1 as next_number
            FROM delivery_orders
            WHERE DATE(created_at) = CURDATE()
        ");

        $next = str_pad((string) ($result[0]->next_number ?? 1), 4, '0', STR_PAD_LEFT);

        return "DO-{$today}-{$next}";
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
            LEFT JOIN fgw_racks r ON pu.fgw_rack_id = r.id
            LEFT JOIN users u ON li.loaded_by = u.id
            WHERE li.delivery_order_id = ?
            ORDER BY li.id DESC
        ", [$deliveryOrderId]);
    }

    public function getVehicles(): array
    {
        return DB::select("
            SELECT id, vehicle_number, vehicle_type, driver_name
            FROM master_vehicles
            WHERE is_active = 1
            ORDER BY vehicle_number ASC
        ");
    }

    public function updateTruckOnDo(int $deliveryOrderId, string $truckNumber, string $driverName = ''): void
    {
        DB::update("
            UPDATE delivery_orders
            SET truck_number = ?, driver_name = ?, updated_at = NOW()
            WHERE id = ?
        ", [trim($truckNumber), trim($driverName), $deliveryOrderId]);
    }

    private function resolveBarcode(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, ',')) {
            $parts = explode(',', $raw);
            return trim(end($parts));
        }
        return $raw;
    }

    public function scanDusToTruck(int $deliveryOrderId, string $packingBarcode, int $userId): object
    {
        $packingBarcode = $this->resolveBarcode($packingBarcode);

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
            ", [$userId, $deliveryOrderId]);

            // Cek apakah SO sudah fully shipped (semua PCS sudah terkirim)
            $do = DB::select("
                SELECT sales_order_id FROM delivery_orders WHERE id = ? LIMIT 1
            ", [$deliveryOrderId]);

            if ($do && $do[0]->sales_order_id) {
                $soId = $do[0]->sales_order_id;

                $check = DB::select("
                    SELECT
                        COALESCE(SUM(sod.qty), 0)                        as total_ordered_pcs,
                        COALESCE((
                            SELECT SUM(pu.qty)
                            FROM loading_items li
                            INNER JOIN packing_units pu ON li.packing_unit_id = pu.id
                            INNER JOIN delivery_orders dox ON li.delivery_order_id = dox.id
                            WHERE dox.sales_order_id = ?
                        ), 0)                                             as total_loaded_pcs
                    FROM sales_order_details sod
                    WHERE sod.sales_order_id = ?
                ", [$soId, $soId]);

                if ($check && (int) $check[0]->total_loaded_pcs >= (int) $check[0]->total_ordered_pcs) {
                    DB::update("
                        UPDATE sales_orders
                        SET status = 'SHIPPED', updated_at = NOW()
                        WHERE id = ?
                    ", [$soId]);
                }
            }

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
                u.name as loaded_by_name,
                so.customer_po_number
            FROM delivery_orders dox
            LEFT JOIN users u ON dox.loaded_by = u.id
            LEFT JOIN sales_orders so ON dox.sales_order_id = so.id
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
            LEFT JOIN fgw_racks r ON pu.fgw_rack_id = r.id
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
