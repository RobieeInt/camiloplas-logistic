<?php

namespace App\Livewire\Pages\FinishGoodsWarehouse;

use App\Services\Logistic\FinishGoodsWarehouseService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showReceiveModal = false;

    public string $trolleyBarcode = '';
    public string $packingBarcode = '';

    public ?object $selectedTrolley = null;

    public array $trolleyItems = [];
    public array $validatedItems = [];

    public int $totalValidated = 0;

    public function scanTrolley(FinishGoodsWarehouseService $service): void
    {
        $this->validate([
            'trolleyBarcode' => ['required'],
        ]);

        try {
            $result = $service->getTrolleyForFgwValidation(
                trim($this->trolleyBarcode)
            );

            $this->selectedTrolley = $result['trolley'];
            $this->trolleyItems = $result['items'];

            $this->validatedItems = [];
            $this->totalValidated = 0;

            $this->showReceiveModal = true;

            $this->dispatch('fgw-scan-opened');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function validateDus(FinishGoodsWarehouseService $service): void
    {
        $this->validate([
            'packingBarcode' => ['required'],
        ]);

        try {
            $result = $service->validateDusInTrolley(
                trolleyId: $this->selectedTrolley->id,
                packingBarcode: trim($this->packingBarcode)
            );

            if (in_array($result->barcode, $this->validatedItems)) {
                throw new \Exception('Dus sudah discan sebelumnya. Jangan spam scanner kayak main dingdong.');
            }

            $this->validatedItems[] = $result->barcode;
            $this->totalValidated = count($this->validatedItems);

            $this->packingBarcode = '';

            session()->flash('scan_success', 'Dus valid diterima FGW.');

            if ($this->totalValidated >= count($this->trolleyItems)) {
                $service->completeFgwReceiving(
                    trolleyId: $this->selectedTrolley->id,
                    userId: auth()->id()
                );

                session()->flash('success', 'Semua dus tervalidasi. Troli diterima FGW.');

                $this->closeModal();

                return;
            }

            $this->dispatch('fgw-ready-again');
        } catch (\Exception $e) {
            session()->flash('scan_error', $e->getMessage());
            $this->dispatch('fgw-ready-again');
        }
    }

    public function closeModal(): void
    {
        $this->showReceiveModal = false;

        $this->reset([
            'trolleyBarcode',
            'packingBarcode',
            'selectedTrolley',
            'trolleyItems',
            'validatedItems',
            'totalValidated',
        ]);

        $this->dispatch('fgw-modal-closed');
    }

    public function render(FinishGoodsWarehouseService $service)
    {
        return view('livewire.pages.finish-goods-warehouse.index', [
            'summary' => $service->summary(),
            'recentTrolleys' => $service->recentReceivedTrolleys(),
        ]);
    }
}
