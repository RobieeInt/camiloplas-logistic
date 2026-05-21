<?php

namespace App\Http\Controllers\Logistic;

use App\Http\Controllers\Controller;
use App\Services\Logistic\LoadingService;

class LoadingPrintController extends Controller
{
    public function deliveryOrder(int $deliveryOrderId, LoadingService $service)
    {
        $data = $service->getPrintDocumentData($deliveryOrderId);

        if ($data['order']->status !== 'LOADED') {
            abort(403, 'DO hanya bisa dicetak setelah status LOADED.');
        }

        $service->markDoAsPrinted($deliveryOrderId);

        $data['isDuplicate'] = $data['order']->do_print_count > 0;

        return view('prints.loading-delivery-order', $data);
    }

    public function suratJalan(int $deliveryOrderId, LoadingService $service)
    {
        $data = $service->getPrintDocumentData($deliveryOrderId);

        if ($data['order']->status !== 'LOADED') {
            abort(403, 'Surat Jalan hanya bisa dicetak setelah status LOADED.');
        }

        $service->markSuratJalanAsPrinted($deliveryOrderId);

        $data['isDuplicate'] = $data['order']->surat_jalan_print_count > 0;

        return view('prints.loading-surat-jalan', $data);
    }
}
