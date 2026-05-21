<div>
    <style>
        @media (max-width: 767.98px) {
            .fgd-summary .fs-2 {
                font-size: 1.5rem !important;
            }

            .fgd-actions {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: .5rem;
            }

            .fgd-table th,
            .fgd-table td {
                font-size: .85rem;
                white-space: nowrap;
            }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Finish Goods Warehouse</h4>
            <div class="text-muted">
                Validasi troli dari Temporary Warehouse dan simpan ke RAK FGW.
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success rounded-3 border-0 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger rounded-3 border-0 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="row g-3 mb-4 fgd-summary">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Menunggu Validasi</div>
                    <div class="fs-2 fw-bold">{{ $summary->waiting_validation }}</div>
                    <div class="small text-muted">Troli dari Temporary Warehouse</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Diterima Hari Ini</div>
                    <div class="fs-2 fw-bold">{{ $summary->received_today }}</div>
                    <div class="small text-muted">Troli masuk FGW</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Total DUS di FGW</div>
                    <div class="fs-2 fw-bold text-primary">{{ number_format($summary->total_dus_fgw) }}</div>
                    <div class="small text-muted">Packing unit tersimpan di rak</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stok DUS per RAK --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-bold mb-1">Stok per RAK FGW</h5>
                    <div class="text-muted small mb-3">Jumlah dus yang tersimpan di setiap rak saat ini.</div>
                </div>
                <div class="text-muted small">
                    Total {{ number_format($summary->total_dus_fgw) }} dus
                </div>
            </div>
        </div>

        <div class="card-body px-4 pb-4">
            @if (count($dusPerRack) > 0)
                <div class="row g-3">
                    @foreach ($dusPerRack as $rack)
                        @php
                            $pct = $summary->total_dus_fgw > 0
                                ? ($rack->total_dus / $summary->total_dus_fgw) * 100
                                : 0;
                            $isEmpty = $rack->total_dus === 0;
                        @endphp
                        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                            <div class="card border rounded-4 h-100 {{ $isEmpty ? 'border-light bg-light' : 'border-primary-subtle' }}">
                                <div class="card-body p-3 text-center">
                                    <div class="fw-bold text-primary mb-1" style="font-size: .7rem; letter-spacing: .05em; text-transform: uppercase;">
                                        {{ $rack->rack_code }}
                                    </div>
                                    <div class="fw-bold {{ $isEmpty ? 'text-muted' : '' }}" style="font-size: 1.6rem; line-height: 1.1;">
                                        {{ number_format($rack->total_dus) }}
                                    </div>
                                    <div class="text-muted" style="font-size: .72rem;">dus</div>

                                    {{-- Mini progress --}}
                                    <div class="progress rounded-pill mt-2" style="height: 4px;">
                                        <div
                                            class="progress-bar {{ $isEmpty ? 'bg-secondary' : 'bg-primary' }}"
                                            style="width: {{ $pct }}%"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2 opacity-25"></i>
                    Belum ada RAK aktif.
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Scan Troli</h5>
            <div class="text-muted small">
                Scan barcode / QR troli dari Temporary Warehouse.
            </div>
        </div>

        <div class="card-body px-4 pb-4">
            <form wire:submit.prevent="scanTrolley">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-10">
                        <label class="form-label fw-semibold">Barcode Troli</label>

                        <input
                            type="text"
                            id="fgwTrolleyInput"
                            wire:model.defer="trolleyBarcode"
                            class="form-control form-control-lg rounded-3"
                            placeholder="Scan barcode troli"
                            autocomplete="off"
                            autofocus
                        >
                    </div>

                    <div class="col-lg-2 d-grid">
                        <button class="btn btn-primary btn-lg rounded-3">
                            <i class="bi bi-upc-scan me-1"></i>
                            Scan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Data Troli Masuk FGW</h5>
            <div class="text-muted small">
                Troli yang sudah diterima FGW beserta lokasi raknya.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fgd-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Troli</th>
                        <th>Barcode</th>
                        <th>RAK</th>
                        <th>Isi</th>
                        <th>Diterima</th>
                        <th>User</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($recentTrolleys as $trolley)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $trolley->trolley_code }}</div>
                                <span class="badge rounded-pill text-bg-success">{{ $trolley->status }}</span>
                            </td>
                            <td><code>{{ $trolley->barcode }}</code></td>
                            <td>
                                <div class="fw-semibold">{{ $trolley->rack_code ?? '-' }}</div>
                                <small class="text-muted">{{ $trolley->rack_name ?? '-' }}</small>
                            </td>
                            <td>{{ $trolley->total_items }} dus</td>
                            <td>
                                {{ $trolley->received_fgw_at ? \Carbon\Carbon::parse($trolley->received_fgw_at)->format('d M Y H:i') : '-' }}
                            </td>
                            <td>{{ $trolley->received_by_name ?? '-' }}</td>
                            <td class="text-end pe-4">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light border rounded-3"
                                    wire:click="openReceivedDetail({{ $trolley->id }})"
                                >
                                    <i class="bi bi-list-check me-1"></i>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada troli masuk FGW.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Receive --}}
    <div
        class="modal fade @if($showReceiveModal) show d-block @endif"
        tabindex="-1"
        style="@if($showReceiveModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Validasi Dus FGW</h5>
                        <div class="text-muted small">
                            Semua dus wajib discan ulang, lalu pilih rak.
                        </div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <form wire:submit.prevent="validateDus">
                    <div class="modal-body">
                        @if (session('scan_success'))
                            <div class="alert alert-success rounded-3 border-0">
                                {{ session('scan_success') }}
                            </div>
                        @endif

                        @if (session('scan_error'))
                            <div class="alert alert-danger rounded-3 border-0">
                                {{ session('scan_error') }}
                            </div>
                        @endif

                        {{-- Info Troli + Progress --}}
                        <div class="border rounded-4 p-3 mb-3 bg-light">
                            <div class="row g-3 align-items-center">
                                <div class="col-lg-6">
                                    <div class="small text-muted">Troli</div>
                                    <div class="fw-bold fs-5">{{ $selectedTrolley->trolley_code ?? '-' }}</div>
                                    <code class="small">{{ $selectedTrolley->barcode ?? '-' }}</code>
                                </div>

                                <div class="col-lg-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-semibold">Progress Validasi</span>
                                        <span class="{{ $totalValidated === count($trolleyItems) && count($trolleyItems) > 0 ? 'text-success fw-bold' : '' }}">
                                            {{ $totalValidated }}/{{ count($trolleyItems) }} dus
                                        </span>
                                    </div>
                                    <div class="progress rounded-3" style="height: 12px;">
                                        <div
                                            class="progress-bar bg-success"
                                            style="width: {{ count($trolleyItems) > 0 ? ($totalValidated / count($trolleyItems)) * 100 : 0 }}%"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Scan Panel --}}
                        <div class="border rounded-4 p-4 mb-3">
                            <div class="row g-4">
                                {{-- Kolom kiri: RAK --}}
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold mb-2">
                                        <i class="bi bi-grid-3x3-gap me-1 text-primary"></i>
                                        RAK Tujuan
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select wire:model="selectedRackId" class="form-select form-select-lg rounded-3 @error('selectedRackId') is-invalid @enderror">
                                        <option value="">Pilih RAK...</option>
                                        @foreach ($racks as $rack)
                                            <option value="{{ $rack->id }}">
                                                {{ $rack->rack_code }} — {{ $rack->rack_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('selectedRackId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Divider vertikal (desktop) --}}
                                <div class="col-md-1 d-none d-md-flex align-items-center justify-content-center">
                                    <div class="vr" style="height: 48px;"></div>
                                </div>

                                {{-- Kolom kanan: Barcode Dus --}}
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-2">
                                        <i class="bi bi-upc-scan me-1 text-primary"></i>
                                        Barcode Dus
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <input
                                            type="text"
                                            id="fgwPackingInput"
                                            wire:model.defer="packingBarcode"
                                            class="form-control rounded-start-3 text-center fw-bold"
                                            placeholder="Scan barcode dus..."
                                            autocomplete="off"
                                        >
                                        <button type="submit" class="btn btn-primary px-4 rounded-end-3">
                                            <i class="bi bi-upc-scan me-1"></i> Scan
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Hint --}}
                            <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Pilih RAK, lalu scan dus. Tiap dus bisa diarahkan ke RAK berbeda.
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-success rounded-3 px-4"
                                    wire:click="completeReceiving"
                                    @disabled($totalValidated < count($trolleyItems) || count($trolleyItems) === 0)
                                >
                                    <i class="bi bi-check-circle me-1"></i>
                                    Complete Receive
                                </button>
                            </div>
                        </div>

                        {{-- Tabel Dus --}}
                        @php
                            $racksById = collect($racks)->keyBy('id');

                            $waitingItems = collect($trolleyItems)
                                ->filter(fn ($item) => !isset($validatedItems[$item->barcode]))
                                ->values();

                            $validatedItemRows = collect($trolleyItems)
                                ->filter(fn ($item) => isset($validatedItems[$item->barcode]))
                                ->values();

                            $sortedTrolleyItems = $waitingItems->concat($validatedItemRows);
                        @endphp

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Box</th>
                                        <th>Item</th>
                                        <th>SPK</th>
                                        <th>Qty</th>
                                        <th>RAK</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($sortedTrolleyItems as $item)
                                        @php
                                            $isValidated  = isset($validatedItems[$item->barcode]);
                                            $assignedRack = $isValidated
                                                ? ($racksById[$validatedItems[$item->barcode]['rack_id']] ?? null)
                                                : null;
                                        @endphp

                                        <tr class="{{ $isValidated ? 'table-success' : '' }}">
                                            <td><code>{{ $item->barcode }}</code></td>
                                            <td>{{ $item->box_number }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $item->item_name }}</div>
                                                <small class="text-muted">{{ $item->item_code }}</small>
                                            </td>
                                            <td>{{ $item->spk_number }}</td>
                                            <td>{{ number_format($item->qty, 0, ',', '.') }} {{ $item->uom }}</td>
                                            <td>
                                                @if ($assignedRack)
                                                    <span class="badge text-bg-primary rounded-pill">{{ $assignedRack->rack_code }}</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isValidated)
                                                    <span class="badge text-bg-success rounded-pill">VALIDATED</span>
                                                @else
                                                    <span class="badge text-bg-light border rounded-pill">WAITING</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Detail Received --}}
    <div
        class="modal fade @if($showReceivedDetailModal) show d-block @endif"
        tabindex="-1"
        style="@if($showReceivedDetailModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Detail Troli Masuk FGW</h5>
                        <div class="text-muted small">
                            Detail barang yang sudah diterima FGW.
                        </div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closeReceivedDetail"></button>
                </div>

                <div class="modal-body">
                    @if ($receivedDetailTrolley)
                        <div class="border rounded-4 p-3 mb-3 bg-light">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="small text-muted">Troli</div>
                                    <div class="fw-bold">{{ $receivedDetailTrolley->trolley_code }}</div>
                                    <code>{{ $receivedDetailTrolley->barcode }}</code>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">RAK</div>
                                    <div class="fw-bold">{{ $receivedDetailTrolley->rack_code ?? '-' }}</div>
                                    <small>{{ $receivedDetailTrolley->rack_name ?? '-' }}</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Diterima</div>
                                    <div>
                                        {{ $receivedDetailTrolley->received_fgw_at ? \Carbon\Carbon::parse($receivedDetailTrolley->received_fgw_at)->format('d M Y H:i') : '-' }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Total Dus</div>
                                    <div class="fw-bold">{{ $receivedDetailTrolley->total_items }} dus</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Barcode</th>
                                    <th>Box</th>
                                    <th>Item</th>
                                    <th>SPK</th>
                                    <th>Qty</th>
                                    <th>Lokasi RAK</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php $lastRack = null; @endphp
                                @forelse ($receivedDetailItems as $item)
                                    @if ($item->rack_code !== $lastRack)
                                        <tr class="table-primary">
                                            <td colspan="7" class="ps-3 py-2">
                                                <i class="bi bi-grid-3x3-gap me-1"></i>
                                                <span class="fw-semibold">RAK {{ $item->rack_code ?? '—' }}</span>
                                                @if ($item->rack_name)
                                                    <span class="text-muted small ms-1">— {{ $item->rack_name }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @php $lastRack = $item->rack_code; @endphp
                                    @endif
                                    <tr>
                                        <td class="ps-3"><code>{{ $item->barcode }}</code></td>
                                        <td>{{ $item->box_number }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->item_name }}</div>
                                            <small class="text-muted">{{ $item->item_code }}</small>
                                        </td>
                                        <td>{{ $item->spk_number }}</td>
                                        <td>{{ number_format($item->qty, 0, ',', '.') }} {{ $item->uom }}</td>
                                        <td>
                                            @if ($item->rack_code)
                                                <span class="badge text-bg-primary rounded-pill">{{ $item->rack_code }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill text-bg-success">{{ $item->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Detail kosong.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" wire:click="closeReceivedDetail" class="btn btn-light border rounded-3 px-4">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        function focusFgwPacking() {
            setTimeout(() => {
                const input = document.getElementById('fgwPackingInput');
                if (input) {
                    input.value = '';
                    input.focus();
                    input.select();
                }
            }, 150);
        }

        function focusFgwTrolley() {
            setTimeout(() => {
                const input = document.getElementById('fgwTrolleyInput');
                if (input) {
                    input.value = '';
                    input.focus();
                    input.select();
                }
            }, 150);
        }

        Livewire.on('fgw-scan-opened', () => {
            focusFgwPacking();
        });

        Livewire.on('fgw-ready-again', () => {
            focusFgwPacking();
        });

        Livewire.on('fgw-modal-closed', () => {
            focusFgwTrolley();
        });

        Livewire.on('fgw-focus-trolley', () => {
            focusFgwTrolley();
        });
    </script>
    @endscript
</div>
