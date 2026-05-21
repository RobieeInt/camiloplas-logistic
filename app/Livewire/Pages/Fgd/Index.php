<?php

namespace App\Livewire\Pages\Fgd;

use App\Services\Logistic\FgdService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showReceiveModal = false;
    public bool $showReceivedDetailModal = false;

    public string $trolleyBarcode = '';
    public string $packingBarcode = '';

    public ?int $selectedRackId = null;

    public ?object $selectedTrolley = null;
    public ?object $receivedDetailTrolley = null;

    public array $trolleyItems = [];
    public array $validatedItems = [];
    public array $receivedDetailItems = [];

    public int $totalValidated = 0;

    public function scanTrolley(FgdService $service): void
    {
        $this->validate([
            'trolleyBarcode' => ['required'],
        ]);

        try {
            $result = $service->getTrolleyForFgwValidation(trim($this->trolleyBarcode));

            $this->selectedTrolley = $result['trolley'];
            $this->trolleyItems = $result['items'];
            $this->validatedItems = [];
            $this->totalValidated = 0;
            $this->packingBarcode = '';
            $this->selectedRackId = null;

            $this->showReceiveModal = true;

            $this->dispatch('fgw-scan-opened');
        } catch (\Exception $e) {
            $this->trolleyBarcode = '';
            session()->flash('error', $e->getMessage());
            $this->dispatch('fgw-focus-trolley');
        }
    }

    public function validateDus(FgdService $service): void
    {
        $this->validate([
            'packingBarcode'  => ['required'],
            'selectedRackId'  => ['required', 'integer'],
        ], [
            'selectedRackId.required' => 'Pilih RAK FGW terlebih dahulu sebelum scan dus.',
            'selectedRackId.integer'  => 'RAK tidak valid.',
        ]);

        try {
            $result = $service->validateDusInTrolley(
                trolleyId: $this->selectedTrolley->id,
                packingBarcode: trim($this->packingBarcode)
            );

            if (isset($this->validatedItems[$result->barcode])) {
                $this->packingBarcode = '';
                session()->flash('scan_error', 'Dus sudah discan sebelumnya.');
                $this->dispatch('fgw-ready-again');
                return;
            }

            // Simpan barcode → [rack_id, packing_unit_id]
            $this->validatedItems[$result->barcode] = [
                'rack_id'        => (int) $this->selectedRackId,
                'packing_unit_id' => (int) $result->id,
            ];

            $this->totalValidated = count($this->validatedItems);
            $this->packingBarcode = '';

            session()->flash('scan_success', 'Dus valid.');

            $this->dispatch('fgw-ready-again');
        } catch (\Exception $e) {
            $this->packingBarcode = '';
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('fgw-ready-again');
        }
    }

    public function completeReceiving(FgdService $service): void
    {
        try {
            if (!$this->selectedTrolley) {
                throw new \Exception('Troli belum dipilih.');
            }

            if ($this->totalValidated < count($this->trolleyItems)) {
                throw new \Exception('Dus belum lengkap divalidasi.');
            }

            // Bangun map: [packing_unit_id => rack_id]
            $packingRackMap = [];
            foreach ($this->validatedItems as $data) {
                $packingRackMap[$data['packing_unit_id']] = $data['rack_id'];
            }

            $service->completeFgwReceiving(
                trolleyId: $this->selectedTrolley->id,
                packingRackMap: $packingRackMap,
                userId: auth()->id()
            );

            session()->flash('success', 'Semua dus tervalidasi. Troli diterima FGW dan dus tersimpan ke rak masing-masing.');

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('fgw-ready-again');
        }
    }

    public function openReceivedDetail(int $trolleyId, FgdService $service): void
    {
        try {
            $data = $service->getReceivedTrolleyDetail($trolleyId);

            $this->receivedDetailTrolley = $data['trolley'];
            $this->receivedDetailItems = $data['items'];
            $this->showReceivedDetailModal = true;
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeReceivedDetail(): void
    {
        $this->showReceivedDetailModal = false;
        $this->receivedDetailTrolley = null;
        $this->receivedDetailItems = [];
    }

    public function closeModal(): void
    {
        $this->showReceiveModal = false;

        $this->reset([
            'trolleyBarcode',
            'packingBarcode',
            'selectedRackId',
            'selectedTrolley',
            'trolleyItems',
            'validatedItems',
            'totalValidated',
        ]);

        $this->dispatch('fgw-modal-closed');
        $this->dispatch('fgw-focus-trolley');
    }

    public function render(FgdService $service)
    {
        return view('livewire.pages.fgd.index', [
            'summary'      => $service->summary(),
            'racks'        => $service->getActiveRacks(),
            'dusPerRack'   => $service->dusPerRack(),
            'recentTrolleys' => $service->recentReceivedTrolleys(),
        ]);
    }
}
