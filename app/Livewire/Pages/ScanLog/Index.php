<?php

namespace App\Livewire\Pages\ScanLog;

use App\Services\Logistic\ScanLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── Filter ───────────────────────────────────────
    public string $search       = '';
    public string $filterStatus = '';
    public string $filterDate   = '';

    // ── Scan + Detail modal ──────────────────────────
    public string $scanBarcode     = '';
    public bool   $showDetailModal = false;
    public ?array $detail          = null;

    // ── BOM sub-modal ────────────────────────────────
    public bool  $showBomModal    = false;
    public array $bomItems        = [];

    // ── Filter watchers ──────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterDate(): void   { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->search       = '';
        $this->filterStatus = '';
        $this->filterDate   = '';
        $this->resetPage();
    }

    // ── Scan ─────────────────────────────────────────
    public function scan(ScanLogService $service): void
    {
        $this->validate(['scanBarcode' => ['required', 'string']]);

        try {
            $this->detail          = $service->lookupBarcode($this->scanBarcode);
            $this->scanBarcode     = '';
            $this->showDetailModal = true;
            $this->resetValidation();
            $this->dispatch('scan-log-focus-input');
        } catch (\Exception $e) {
            $this->scanBarcode = '';
            session()->flash('scan_error', $e->getMessage());
            $this->resetValidation();
            $this->dispatch('scan-log-focus-input');
        }
    }

    // ── Detail modal ─────────────────────────────────
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detail          = null;
        $this->bomItems        = [];
        $this->showBomModal    = false;
        $this->dispatch('scan-log-focus-input');
    }

    // ── BOM modal ────────────────────────────────────
    public function openBomModal(): void
    {
        $this->bomItems     = $this->detail['bom_items'] ?? [];
        $this->showBomModal = true;
    }

    public function closeBomModal(): void
    {
        $this->showBomModal = false;
    }

    // ── Render ───────────────────────────────────────
    public function render(ScanLogService $service)
    {
        return view('livewire.pages.scan-log.index', [
            'summary' => $service->summary(),
            'logs'    => $service->getLogs(
                search: $this->search,
                status: $this->filterStatus,
                date:   $this->filterDate,
                page:   $this->getPage(),
            ),
        ]);
    }
}
