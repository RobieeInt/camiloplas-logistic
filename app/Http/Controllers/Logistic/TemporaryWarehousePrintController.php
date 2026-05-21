<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Services\Logistic\TemporaryWarehouseService;
use Illuminate\Http\Request;

class TemporaryWarehousePrintController extends Controller
{
    public function labels(Request $request, TemporaryWarehouseService $service)
    {
        $batchId = $request->query('batch_id');
        $packingUnitId = $request->query('packing_unit_id');

        if ($batchId) {
            $labels = $service->getPrintLabels($batchId);
            $title = 'Batch: ' . $batchId;
        } elseif ($packingUnitId) {
            $labels = $service->getPrintLabelByPackingUnitId((int) $packingUnitId);
            $title = 'Reprint Label';
        } else {
            abort(404, 'Parameter print tidak ditemukan.');
        }

        if (count($labels) === 0) {
            abort(404, 'Data label kosong.');
        }

        return view('prints.temporary-warehouse-labels', [
            'labels' => $labels,
            'batchId' => $batchId ?? 'REPRINT-' . $packingUnitId,
            'title' => $title,
        ]);
    }

    public function trolleyQr(Request $request, TemporaryWarehouseService $service)
    {
        $trolleyId = (int) $request->query('trolley_id');

        if (!$trolleyId) {
            abort(404, 'Troli tidak ditemukan.');
        }

        $trolley = $service->getTrolleyQrLabel($trolleyId);

        if (!$trolley) {
            abort(404, 'Data troli kosong.');
        }

        return view('prints.temporary-warehouse-trolley-qr', [
            'trolley' => $trolley,
        ]);
    }
}
