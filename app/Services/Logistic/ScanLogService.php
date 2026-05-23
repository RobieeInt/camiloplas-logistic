<?php

namespace App\Services\Logistic;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ScanLogService
{
    public function summary(): object
    {
        $result = DB::select("
            SELECT
                COUNT(*)                                                                    as total,
                SUM(CASE WHEN status = 'PRINTED' THEN 1 ELSE 0 END)                       as total_printed,
                SUM(CASE WHEN status = 'TW_SCANNED' THEN 1 ELSE 0 END)                    as total_tw_scanned,
                SUM(CASE WHEN status = 'SCANNED_TO_TROLLEY' THEN 1 ELSE 0 END)             as total_in_trolley,
                SUM(CASE WHEN status IN ('SENT_FGW','RECEIVED_FGW') THEN 1 ELSE 0 END)    as total_fgw,
                SUM(CASE WHEN status = 'LOADED' THEN 1 ELSE 0 END)                        as total_loaded
            FROM packing_units
        ");

        return $result[0];
    }

    public function getLogs(
        string $search = '',
        string $status = '',
        string $date   = '',
        int $perPage   = 20,
        int $page      = 1
    ): LengthAwarePaginator {
        $offset   = ($page - 1) * $perPage;
        $clauses  = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $clauses[]  = "(pu.barcode LIKE ? OR pu.box_number LIKE ? OR po.spk_number LIKE ? OR i.item_name LIKE ? OR i.item_code LIKE ? OR pu.batch_number LIKE ?)";
            $term       = "%{$search}%";
            $bindings   = array_merge($bindings, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($status)) {
            $clauses[]  = "pu.status = ?";
            $bindings[] = $status;
        }

        match ($date) {
            'today' => $clauses[] = "DATE(pu.printed_at) = CURDATE()",
            'week'  => $clauses[] = "pu.printed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month' => $clauses[] = "pu.printed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            default => null,
        };

        $where = implode(' AND ', $clauses);

        $total = DB::select("
            SELECT COUNT(*) as total
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            WHERE {$where}
        ", $bindings)[0]->total ?? 0;

        $data = DB::select("
            SELECT
                pu.id,
                pu.box_number,
                pu.barcode,
                pu.qty,
                pu.uom,
                pu.status,
                pu.batch_number,
                pu.lot_number,
                pu.printed_at,
                pu.prod_scanned_at,
                po.spk_number,
                po.factory,
                i.item_code,
                i.item_name,
                u_print.name  as printed_by_name,
                u_scan.name   as tw_scanned_by_name,
                bpl.berat,
                bpl.qc_operator,
                rs.operator,
                rs.mesin_kode,
                rs.shift
            FROM packing_units pu
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            INNER JOIN items i ON pu.item_id = i.id
            LEFT JOIN users u_print ON pu.printed_by = u_print.id
            LEFT JOIN users u_scan  ON pu.prod_scanned_by = u_scan.id
            LEFT JOIN batch_pickup_log bpl ON bpl.batch_number = pu.batch_number
            LEFT JOIN rollsheet rs ON rs.lot_number = bpl.lot_number
            WHERE {$where}
            ORDER BY pu.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $bindings);

        return new LengthAwarePaginator($data, $total, $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function lookupBarcode(string $barcode): array
    {
        $barcode = trim($barcode);
        if (str_contains($barcode, ',')) {
            $parts   = explode(',', $barcode);
            $barcode = trim(end($parts));
        }

        $puResult = DB::select("
            SELECT
                pu.id, pu.box_number, pu.barcode, pu.qty, pu.uom, pu.status,
                pu.batch_number, pu.lot_number, pu.printed_at, pu.prod_scanned_at,
                pu.production_order_id,
                i.item_name, i.item_code,
                po.spk_number
            FROM packing_units pu
            INNER JOIN items i ON pu.item_id = i.id
            INNER JOIN production_orders po ON pu.production_order_id = po.id
            WHERE pu.barcode = ?
            LIMIT 1
        ", [$barcode]);

        if (!$puResult) {
            throw new \Exception("Barcode '{$barcode}' tidak ditemukan.");
        }

        $pu = $puResult[0];

        // ── SPK dulu — batch fallback butuh spk.id ───────────────────────────
        $spk      = null;
        $bomItems = [];
        $runs     = [];

        if (!empty($pu->spk_number)) {
            $spkRow = DB::select("
                SELECT id, spk_number, type, factory, department, product,
                       qty, mesin, status, tanggal, delivery_date, ref_so
                FROM spk
                WHERE spk_number = ?
                LIMIT 1
            ", [$pu->spk_number]);

            if ($spkRow) {
                $spk = $spkRow[0];

                $bomItems = DB::select("
                    SELECT material, kebutuhan, satuan, stok_tersedia, requested_qty, issued_qty
                    FROM spk_bom_items
                    WHERE spk_id = ?
                    ORDER BY id ASC
                ", [$spk->id]);

                $runs = DB::select("
                    SELECT run_number, mesin_kode, mesin_nama, factory, product,
                           qty_target, operator, status, started_at, completed_at,
                           qty_ok, qty_reject
                    FROM production_runs
                    WHERE spk_id = ?
                    ORDER BY id ASC
                ", [$spk->id]);
            }
        }

        // ── batches: direct via pu.batch_number (1 baris) ATAU semua via spk_id ─
        // Rollsheet di-JOIN langsung supaya operator/mesin/shift sudah ada per batch.
        $batches     = [];
        $batchViaSpk = false;

        $batchSql = "
            SELECT
                bpl.batch_number, bpl.lot_number, bpl.qc_operator, bpl.berat,
                bpl.spk_id, bpl.status, bpl.qc_printed_at,
                rs.operator, rs.mesin_kode, rs.shift, rs.qc_status as rs_qc_status
            FROM batch_pickup_log bpl
            LEFT JOIN rollsheet rs ON rs.lot_number = bpl.lot_number
        ";

        if (!empty($pu->batch_number)) {
            $rows = DB::select($batchSql . "WHERE bpl.batch_number = ? LIMIT 1", [$pu->batch_number]);
            $batches = array_map(fn ($r) => (array) $r, $rows);
        }

        if (empty($batches) && $spk) {
            // Barcode dicetak via SPK langsung → batch_number NULL, ambil semua batch SPK
            $rows    = DB::select($batchSql . "WHERE bpl.spk_id = ? ORDER BY bpl.id ASC", [$spk->id]);
            $batches = array_map(fn ($r) => (array) $r, $rows);
            if (!empty($batches)) $batchViaSpk = true;
        }

        // ── Logistics chain: TW → trolley → FGW → loading → DO ──────────
        $logisticsResult = DB::select("
            SELECT
                u_print.name            AS printed_by,
                pu.prod_scanned_at      AS tw_scanned_at,
                u_tw.name               AS tw_scanned_by,
                t.trolley_code,
                ti.scanned_at           AS trolley_scanned_at,
                u_ti.name               AS trolley_scanned_by,
                fr.rack_code,
                fr.rack_name,
                t.received_fgw_at,
                u_fgw.name              AS fgw_received_by,
                li.loaded_at,
                u_load.name             AS loaded_by,
                d.do_number,
                d.so_number,
                d.customer_name,
                d.customer_address,
                d.truck_number,
                d.driver_name,
                d.status                AS do_status,
                d.do_first_printed_at,
                d.surat_jalan_first_printed_at
            FROM packing_units pu
            LEFT JOIN users u_print    ON pu.printed_by            = u_print.id
            LEFT JOIN users u_tw       ON pu.prod_scanned_by        = u_tw.id
            LEFT JOIN trolley_items ti ON ti.packing_unit_id        = pu.id
            LEFT JOIN trolleys t       ON ti.trolley_id             = t.id
            LEFT JOIN fgw_racks fr     ON pu.fgw_rack_id            = fr.id
            LEFT JOIN users u_ti       ON ti.scanned_by             = u_ti.id
            LEFT JOIN users u_fgw      ON t.received_fgw_by         = u_fgw.id
            LEFT JOIN loading_items li ON li.packing_unit_id        = pu.id
            LEFT JOIN delivery_orders d ON li.delivery_order_id     = d.id
            LEFT JOIN users u_load     ON li.loaded_by              = u_load.id
            WHERE pu.id = ?
            LIMIT 1
        ", [$pu->id]);

        $logistics = $logisticsResult ? (array) $logisticsResult[0] : [];

        return [
            'packing_unit'  => (array) $pu,
            'batches'       => $batches,
            'batch_via_spk' => $batchViaSpk,
            'spk'           => $spk ? (array) $spk : null,
            'bom_items'     => array_map(fn ($i) => (array) $i, $bomItems),
            'runs'          => array_map(fn ($r) => (array) $r, $runs),
            'logistics'     => $logistics,
        ];
    }
}
