<?php

namespace App\Livewire\Pages\TemporaryWarehouse;

use App\Services\Logistic\TemporaryWarehouseService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    // Print modal
    public bool $showPrintModal = false;
    public ?int $selectedSpkId = null;
    public int $totalBox = 1;
    public int $qtyPerBox = 1000;

    // Scan modal (TW first scan)
    public bool $showScanModal = false;
    public string $twScanBarcode = '';
    public ?array $scanResult = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ── Print ────────────────────────────────────────

    public function openPrintModal(): void
    {
        $this->selectedSpkId = null;
        $this->totalBox = 1;
        $this->qtyPerBox = 1000;
        $this->showPrintModal = true;
    }

    public function closePrintModal(): void
    {
        $this->showPrintModal = false;
        $this->resetValidation();
    }

    public function printBarcode(TemporaryWarehouseService $service): void
    {
        $this->validate([
            'selectedSpkId' => ['required', 'integer'],
            'totalBox'      => ['required', 'integer', 'min:1', 'max:500'],
            'qtyPerBox'     => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'selectedSpkId.required' => 'Pilih SPK terlebih dahulu.',
            'qtyPerBox.required'     => 'Qty per dus wajib diisi.',
            'qtyPerBox.min'          => 'Qty per dus minimal 1 PCS.',
            'qtyPerBox.max'          => 'Qty per dus maksimal 100.000 PCS.',
        ]);

        try {
            $batchId = $service->printBarcode(
                spkId: $this->selectedSpkId,
                totalBox: $this->totalBox,
                qtyPerBox: $this->qtyPerBox,
                userId: auth()->id(),
            );

            $this->showPrintModal = false;
            $this->reset(['selectedSpkId', 'totalBox', 'qtyPerBox']);
            $this->totalBox = 1;
            $this->qtyPerBox = 1000;

            $this->redirectRoute('temporary-warehouse.print-labels', [
                'batch_id' => $batchId,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ── Scan Produksi (TW first scan) ────────────────

    public function openScanModal(): void
    {
        $this->twScanBarcode = '';
        $this->scanResult = null;
        $this->showScanModal = true;
        $this->resetValidation();
        $this->dispatch('tw-scan-modal-opened');
    }

    public function closeScanModal(): void
    {
        $this->showScanModal = false;
        $this->twScanBarcode = '';
        $this->scanResult = null;
        $this->resetValidation();
        $this->dispatch('tw-scan-modal-closed');
    }

    public function scanProd(TemporaryWarehouseService $service): void
    {
        $this->validate([
            'twScanBarcode' => ['required', 'string'],
        ]);

        try {
            $result = $service->scanProdBarcode(
                barcode: trim($this->twScanBarcode),
                userId: auth()->id()
            );

            $this->twScanBarcode = '';
            $this->scanResult = (array) $result;

            $this->dispatch('tw-scan-ready-again');
        } catch (\Exception $e) {
            $this->twScanBarcode = '';
            $this->scanResult = null;
            session()->flash('tw_scan_error', $e->getMessage());
            $this->dispatch('tw-scan-ready-again');
        }
    }

    public function render(TemporaryWarehouseService $service)
    {
        $availableBatches = $this->selectedSpkId
            ? $service->getAvailableBatches((int) $this->selectedSpkId)
            : [];

        return view('livewire.pages.temporary-warehouse.index', [
            'summary'          => $service->summary(),
            'spkList'          => $service->getSpkList(),
            'availableBatches' => $availableBatches,
            'packingUnits'     => $service->getPackingUnits(
                search: $this->search,
                page: $this->getPage()
            ),
        ]);
    }
}
