<?php

namespace App\Livewire\Pages\Loading;

use App\Services\Logistic\LoadingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showLoadingModal = false;

    public ?object $selectedOrder = null;
    public array $orderItems = [];
    public array $loadedItems = [];

    public string $packingBarcode = '';

    public function openLoadingModal(int $deliveryOrderId, LoadingService $service): void
    {
        try {
            $data = $service->getOrderDetail($deliveryOrderId);

            $this->selectedOrder = $data['order'];
            $this->orderItems = $data['items'];
            $this->loadedItems = $service->loadedItems($deliveryOrderId);
            $this->packingBarcode = '';
            $this->showLoadingModal = true;

            $this->dispatch('loading-modal-opened');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function scanDus(LoadingService $service): void
    {
        $this->validate([
            'packingBarcode' => ['required', 'string'],
        ]);

        try {
            if (!$this->selectedOrder) {
                throw new \Exception('DO belum dipilih.');
            }

            $service->scanDusToTruck(
                deliveryOrderId: $this->selectedOrder->id,
                packingBarcode: trim($this->packingBarcode),
                userId: auth()->id()
            );

            $data = $service->getOrderDetail($this->selectedOrder->id);

            $this->selectedOrder = $data['order'];
            $this->orderItems = $data['items'];
            $this->loadedItems = $service->loadedItems($this->selectedOrder->id);
            $this->packingBarcode = '';

            session()->flash('scan_success', 'Dus berhasil masuk truck.');

            $this->dispatch('loading-ready-again');
        } catch (\Exception $e) {
            $this->packingBarcode = '';
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('loading-ready-again');
        }
    }

    public function completeLoading(LoadingService $service): void
    {
        try {
            if (!$this->selectedOrder) {
                throw new \Exception('DO belum dipilih.');
            }

            $service->completeLoading($this->selectedOrder->id, auth()->id());

            session()->flash('success', 'Loading complete. Dokumen sudah bisa dicetak.');

            $this->closeLoadingModal();
        } catch (\Exception $e) {
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('loading-ready-again');
        }
    }

    public function closeLoadingModal(): void
    {
        $this->showLoadingModal = false;

        $this->reset([
            'selectedOrder',
            'orderItems',
            'loadedItems',
            'packingBarcode',
        ]);

        $this->dispatch('loading-modal-closed');
    }

    public function render(LoadingService $service)
    {
        return view('livewire.pages.loading.index', [
            'summary' => $service->summary(),
            'readyOrders' => $service->readyOrders(),
            'recentLoadedOrders' => $service->recentLoadedOrders(),
        ]);
    }
}
