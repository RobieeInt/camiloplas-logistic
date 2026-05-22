<?php

namespace App\Livewire\Pages\TemporaryWarehouseQc;

use App\Services\Logistic\TemporaryWarehouseService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showCreateTrolleyModal = false;
    public bool $showScanModal          = false;
    public bool $showTrolleyDetailModal = false;

    public ?int $createTrolleyItemId  = null;
    public ?int $createTrolleyCapacity = null;

    public ?int    $selectedTrolleyId       = null;
    public string  $selectedTrolleyCode     = '';
    public string  $selectedTrolleyBarcode  = '';
    public ?int    $selectedTrolleyCapacity = null;
    public int     $selectedTrolleyTotalItems = 0;
    public string  $selectedTrolleyItemName = '';

    public string $packingBarcode = '';

    public ?object $detailTrolley     = null;
    public array   $detailTrolleyItems = [];

    public function openCreateTrolleyModal(): void
    {
        $this->createTrolleyItemId  = null;
        $this->createTrolleyCapacity = null;
        $this->showCreateTrolleyModal = true;
        $this->resetValidation();
    }

    public function closeCreateTrolleyModal(): void
    {
        $this->showCreateTrolleyModal = false;
        $this->resetValidation();
    }

    public function submitCreateTrolley(TemporaryWarehouseService $service): void
    {
        $this->validate([
            'createTrolleyItemId' => ['required', 'integer'],
        ], [
            'createTrolleyItemId.required' => 'Pilih item untuk troli ini.',
        ]);

        try {
            $service->createTrolley(
                userId: auth()->id(),
                capacity: $this->createTrolleyCapacity > 0 ? (int) $this->createTrolleyCapacity : null,
                itemId: (int) $this->createTrolleyItemId
            );

            $this->showCreateTrolleyModal = false;
            $this->reset(['createTrolleyItemId', 'createTrolleyCapacity']);
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
        int $totalItems,
        string $itemName
    ): void {
        $this->selectedTrolleyId       = $trolleyId;
        $this->selectedTrolleyCode     = $trolleyCode;
        $this->selectedTrolleyBarcode  = $trolleyBarcode;
        $this->selectedTrolleyCapacity = $capacity;
        $this->selectedTrolleyTotalItems = $totalItems;
        $this->selectedTrolleyItemName = $itemName;
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
            'selectedTrolleyItemName',
            'packingBarcode',
        ]);

        $this->resetValidation();
        $this->dispatch('scan-modal-closed');
    }

    public function scanDus(TemporaryWarehouseService $service): void
    {
        $this->validate([
            'selectedTrolleyId' => ['required', 'integer'],
            'packingBarcode'    => ['required', 'string'],
        ]);

        try {
            $result = $service->scanDusToSelectedTrolley(
                packingBarcode: trim($this->packingBarcode),
                trolleyId: $this->selectedTrolleyId,
                userId: auth()->id()
            );

            $this->packingBarcode = '';
            $this->selectedTrolleyTotalItems = $result->total_items;
            $this->selectedTrolleyCapacity   = $result->capacity;

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

            $this->detailTrolley     = $data['trolley'];
            $this->detailTrolleyItems = $data['items'];

            $this->showTrolleyDetailModal = true;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeTrolleyDetailModal(): void
    {
        $this->showTrolleyDetailModal = false;
        $this->detailTrolley     = null;
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
            $this->detailTrolley     = $data['trolley'];
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
        return view('livewire.pages.temporary-warehouse-qc.index', [
            'summary'          => $service->summary(),
            'items'            => $service->getItems(),
            'trolleys'         => $service->getOpenTrolleys(),
        ]);
    }
}
