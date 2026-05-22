<?php

namespace App\Livewire\Pages\Loading;

use App\Services\Logistic\LoadingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    // ── Loading modal ────────────────────────────
    public bool $showLoadingModal = false;

    public ?object $selectedOrder = null;
    public array $orderItems = [];
    public array $loadedItems = [];

    public string $packingBarcode = '';
    public string $truckNumber = '';
    public string $driverName = '';
    public ?int $selectedVehicleId = null;

    // ── Buat DO modal ────────────────────────────
    public bool   $showCreateDoModal = false;
    public string $createDoNumber    = '';
    public ?int   $createDoAddSoId   = null;   // dropdown selection (temp)
    public array  $createDoSoIds     = [];     // confirmed selected SO IDs
    public array  $createDoSos       = [];     // confirmed selected SO objects
    public array  $createDoDetails   = [];     // merged items dari semua SO terpilih
    public array  $createDoBoxes     = [];     // [item_id => required_boxes]

    // ── Buat DO methods ───────────────────────────

    public function openCreateDoModal(LoadingService $service): void
    {
        $this->createDoNumber   = $service->nextDoNumber();
        $this->createDoAddSoId  = null;
        $this->createDoSoIds    = [];
        $this->createDoSos      = [];
        $this->createDoDetails  = [];
        $this->createDoBoxes    = [];
        $this->showCreateDoModal = true;
        $this->resetValidation();
    }

    public function addSoToDo(LoadingService $service): void
    {
        if (!$this->createDoAddSoId) {
            return;
        }

        $soId = (int) $this->createDoAddSoId;

        if (in_array($soId, $this->createDoSoIds)) {
            $this->createDoAddSoId = null;
            session()->flash('create_do_error', 'SO ini sudah ditambahkan.');
            return;
        }

        $this->createDoSoIds[] = $soId;
        $this->createDoAddSoId = null;

        $this->refreshCreateDoDetails($service);
    }

    public function removeSoFromDo(int $soId, LoadingService $service): void
    {
        $this->createDoSoIds = array_values(
            array_filter($this->createDoSoIds, fn ($id) => $id !== $soId)
        );

        $this->refreshCreateDoDetails($service);
    }

    private function refreshCreateDoDetails(LoadingService $service): void
    {
        if (empty($this->createDoSoIds)) {
            $this->createDoSos     = [];
            $this->createDoDetails = [];
            $this->createDoBoxes   = [];
            return;
        }

        try {
            $data = $service->getMultipleSoDetails($this->createDoSoIds);
            $this->createDoSos     = $data['sos'];
            $this->createDoDetails = $data['details'];

            // Preserve existing box inputs, pre-fill blank for new items
            $merged = [];
            foreach ($data['details'] as $detail) {
                $merged[$detail->item_id] = $this->createDoBoxes[$detail->item_id] ?? '';
            }
            $this->createDoBoxes = $merged;
        } catch (\Exception $e) {
            session()->flash('create_do_error', $e->getMessage());
        }
    }

    public function submitCreateDo(LoadingService $service): void
    {
        $this->validate([
            'createDoNumber' => ['required', 'string', 'max:100'],
        ], [
            'createDoNumber.required' => 'DO Number wajib diisi.',
        ]);

        if (empty($this->createDoSoIds)) {
            session()->flash('create_do_error', 'Pilih minimal 1 SO terlebih dahulu.');
            return;
        }

        try {
            $service->createDoFromSos(
                soIds: $this->createDoSoIds,
                doNumber: $this->createDoNumber,
                requiredBoxes: $this->createDoBoxes,
                userId: auth()->id()
            );

            session()->flash('success', "DO {$this->createDoNumber} berhasil dibuat.");
            $this->closeCreateDoModal();
        } catch (\Exception $e) {
            session()->flash('create_do_error', $e->getMessage());
        }
    }

    public function closeCreateDoModal(): void
    {
        $this->showCreateDoModal = false;
        $this->reset([
            'createDoNumber',
            'createDoAddSoId',
            'createDoSoIds',
            'createDoSos',
            'createDoDetails',
            'createDoBoxes',
        ]);
        $this->resetValidation();
    }

    // ── Loading modal methods ─────────────────────

    public function openLoadingModal(int $deliveryOrderId, LoadingService $service): void
    {
        try {
            $data = $service->getOrderDetail($deliveryOrderId);

            $this->selectedOrder   = $data['order'];
            $this->orderItems      = $data['items'];
            $this->loadedItems     = $service->loadedItems($deliveryOrderId);
            $this->packingBarcode  = '';
            $this->truckNumber     = $data['order']->truck_number ?? '';
            $this->driverName      = $data['order']->driver_name ?? '';
            $this->selectedVehicleId = null;
            $this->showLoadingModal = true;

            $this->dispatch('loading-modal-opened');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function selectVehicle(int $vehicleId, LoadingService $service): void
    {
        $vehicles = $service->getVehicles();
        $vehicle  = collect($vehicles)->firstWhere('id', $vehicleId);

        if ($vehicle) {
            $this->selectedVehicleId = $vehicleId;
            $this->truckNumber = $vehicle->vehicle_number;
            $this->driverName  = $vehicle->driver_name ?? '';
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
        $this->validate([
            'truckNumber' => ['required', 'string', 'min:3'],
            'driverName'  => ['required', 'string', 'min:2'],
        ], [
            'truckNumber.required' => 'No. Polisi truck wajib diisi sebelum complete loading.',
            'truckNumber.min'      => 'No. Polisi terlalu pendek.',
            'driverName.required'  => 'Nama driver wajib diisi sebelum complete loading.',
            'driverName.min'       => 'Nama driver terlalu pendek.',
        ]);

        try {
            if (!$this->selectedOrder) {
                throw new \Exception('DO belum dipilih.');
            }

            $service->updateTruckOnDo($this->selectedOrder->id, $this->truckNumber, $this->driverName);
            $service->completeLoading($this->selectedOrder->id, auth()->id());

            session()->flash('success', 'Loading complete. Dokumen siap dicetak.');

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
            'truckNumber',
            'driverName',
            'selectedVehicleId',
        ]);

        $this->dispatch('loading-modal-closed');
    }

    public function render(LoadingService $service)
    {
        return view('livewire.pages.loading.index', [
            'summary'            => $service->summary(),
            'salesOrders'        => $service->getSalesOrders(),
            'readyOrders'        => $service->readyOrders(),
            'recentLoadedOrders' => $service->recentLoadedOrders(),
            'vehicles'           => $service->getVehicles(),
        ]);
    }
}
