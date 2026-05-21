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

    public bool $showPrintModal = false;
    public bool $showScanModal = false;
    public bool $showTrolleyDetailModal = false;

    public ?int $productionOrderId = null;
    public int $totalBox = 1;

    public ?int $selectedTrolleyId = null;
    public string $selectedTrolleyCode = '';
    public string $selectedTrolleyBarcode = '';
    public ?int $selectedTrolleyCapacity = null;
    public int $selectedTrolleyTotalItems = 0;

    public string $packingBarcode = '';

    public ?object $detailTrolley = null;
    public array $detailTrolleyItems = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openPrintModal(): void
    {
        $this->productionOrderId = null;
        $this->totalBox = 1;
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
            'productionOrderId' => ['required', 'integer'],
            'totalBox' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        try {
            $batchId = $service->printBarcode(
                productionOrderId: $this->productionOrderId,
                totalBox: $this->totalBox,
                userId: auth()->id()
            );

            $this->showPrintModal = false;
            $this->reset(['productionOrderId', 'totalBox']);
            $this->totalBox = 1;

            $this->redirectRoute('temporary-warehouse.print-labels', [
                'batch_id' => $batchId,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function createTrolley(TemporaryWarehouseService $service): void
    {
        try {
            $capacity = null;
            $service->createTrolley(auth()->id(), $capacity);
            session()->flash('success', 'Troli baru berhasil dibuat.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openScanModal(
        int $trolleyId,
        string $trolleyCode,
        string $trolleyBarcode,
        ?int $capacity,
        int $totalItems
    ): void {
        $this->selectedTrolleyId = $trolleyId;
        $this->selectedTrolleyCode = $trolleyCode;
        $this->selectedTrolleyBarcode = $trolleyBarcode;
        $this->selectedTrolleyCapacity = $capacity;
        $this->selectedTrolleyTotalItems = $totalItems;
        $this->packingBarcode = '';

        $this->showScanModal = true;
        $this->resetValidation();

        $this->dispatch('scan-modal-opened');
    }

    public function closeScanModal(): void
    {
        $this->showScanModal = false;

        $this->reset([
            'selectedTrolleyId',
            'selectedTrolleyCode',
            'selectedTrolleyBarcode',
            'selectedTrolleyCapacity',
            'selectedTrolleyTotalItems',
            'packingBarcode',
        ]);

        // $this->selectedTrolleyCapacity = 3;
        $this->resetValidation();

        $this->dispatch('scan-modal-closed');
    }

    public function scanDus(TemporaryWarehouseService $service): void
    {
        $this->validate([
            'selectedTrolleyId' => ['required', 'integer'],
            'packingBarcode' => ['required', 'string'],
        ]);

        try {
            $result = $service->scanDusToSelectedTrolley(
                packingBarcode: trim($this->packingBarcode),
                trolleyId: $this->selectedTrolleyId,
                userId: auth()->id()
            );

            $this->packingBarcode = '';
            $this->selectedTrolleyTotalItems = $result->total_items;
            $this->selectedTrolleyCapacity = $result->capacity;

            session()->flash(
                'scan_success',
                "Dus {$result->packing_barcode} berhasil masuk ke {$result->trolley_code}. Isi troli: {$result->total_items}/" . ($result->capacity ?? '∞')
            );

            if ($result->status === 'COMPLETE') {
                $this->showScanModal = false;
                $this->dispatch('scan-modal-closed');
                session()->flash('success', 'Troli sudah COMPLETE.');
                return;
            }

            $this->dispatch('scan-ready-again');
        } catch (\Exception $e) {
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('scan-ready-again');
        }
    }

    public function openTrolleyDetailModal(int $trolleyId, TemporaryWarehouseService $service): void
    {
        try {
            $data = $service->getTrolleyDetail($trolleyId);

            $this->detailTrolley = $data['trolley'];
            $this->detailTrolleyItems = $data['items'];

            $this->showTrolleyDetailModal = true;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeTrolleyDetailModal(): void
    {
        $this->showTrolleyDetailModal = false;
        $this->detailTrolley = null;
        $this->detailTrolleyItems = [];
    }

    public function removeDusFromTrolley(
        int $trolleyId,
        int $packingUnitId,
        TemporaryWarehouseService $service
    ): void {
        try {
            $service->removeDusFromTrolley(
                trolleyId: $trolleyId,
                packingUnitId: $packingUnitId,
                userId: auth()->id()
            );

            $data = $service->getTrolleyDetail($trolleyId);
            $this->detailTrolley = $data['trolley'];
            $this->detailTrolleyItems = $data['items'];

            session()->flash('detail_success', 'Dus berhasil dikeluarkan dari troli.');
        } catch (\Exception $e) {
            session()->flash('detail_error', $e->getMessage());
        }
    }

    public function forceCompleteTrolley(int $trolleyId, TemporaryWarehouseService $service): void
    {
        try {
            $service->forceCompleteTrolley($trolleyId, auth()->id());
            session()->flash('success', 'Troli berhasil di-force complete.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function sendToFgw(int $trolleyId, TemporaryWarehouseService $service): void
    {
        try {
            $service->sendToFgw($trolleyId, auth()->id());
            session()->flash('success', 'Troli berhasil dikirim ke FGW.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(TemporaryWarehouseService $service)
    {
        // dd($service->getOpenTrolleys());
        return view('livewire.pages.temporary-warehouse.index', [
            'summary' => $service->summary(),
            'productionOrders' => $service->getProductionOrders(),
            'packingUnits' => $service->getPackingUnits(
                search: $this->search,
                page: $this->getPage()
            ),
            'trolleys' => $service->getOpenTrolleys(),
        ]);
    }
}
