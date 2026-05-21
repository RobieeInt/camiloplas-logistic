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
        <div class="col-6 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Menunggu Validasi</div>
                    <div class="fs-2 fw-bold">{{ $summary->waiting_validation }}</div>
                    <div class="small text-muted">Troli dari Temporary Warehouse</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Diterima Hari Ini</div>
                    <div class="fs-2 fw-bold">{{ $summary->received_today }}</div>
                    <div class="small text-muted">Troli masuk FGW</div>
                </div>
            </div>
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

                        <div class="border rounded-4 p-3 mb-3 bg-light">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="small text-muted">Troli</div>
                                    <div class="fw-bold fs-5">{{ $selectedTrolley->trolley_code ?? '-' }}</div>
                                    <code>{{ $selectedTrolley->barcode ?? '-' }}</code>
                                </div>

                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">Pilih RAK FGW</label>
                                    <select wire:model="selectedRackId" class="form-select rounded-3">
                                        <option value="">Pilih RAK</option>
                                        @foreach ($racks as $rack)
                                            <option value="{{ $rack->id }}">
                                                {{ $rack->rack_code }} - {{ $rack->rack_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('selectedRackId')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-lg-4">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Progress Validasi</span>
                                        <span>{{ $totalValidated }}/{{ count($trolleyItems) }}</span>
                                    </div>

                                    <div class="progress" style="height: 10px;">
                                        <div
                                            class="progress-bar bg-success"
                                            style="width: {{ count($trolleyItems) > 0 ? ($totalValidated / count($trolleyItems)) * 100 : 0 }}%"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Barcode Dus</label>

                            <input
                                type="text"
                                id="fgwPackingInput"
                                wire:model.defer="packingBarcode"
                                class="form-control form-control-lg rounded-3 text-center fw-bold"
                                placeholder="Scan barcode dus"
                                autocomplete="off"
                            >
                        </div>

                        <div class="d-flex justify-content-end mb-3">
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

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Box</th>
                                        <th>Item</th>
                                        <th>SPK</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $waitingItems = collect($trolleyItems)
                                            ->filter(fn ($item) => !in_array($item->barcode, $validatedItems))
                                            ->values();

                                        $validatedItemRows = collect($trolleyItems)
                                            ->filter(fn ($item) => in_array($item->barcode, $validatedItems))
                                            ->values();

                                        $sortedTrolleyItems = $waitingItems->concat($validatedItemRows);
                                    @endphp

                                    @foreach ($sortedTrolleyItems as $item)
                                        @php
                                            $isValidated = in_array($item->barcode, $validatedItems);
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
                                    <th>Barcode</th>
                                    <th>Box</th>
                                    <th>Item</th>
                                    <th>SPK</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($receivedDetailItems as $item)
                                    <tr>
                                        <td><code>{{ $item->barcode }}</code></td>
                                        <td>{{ $item->box_number }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->item_name }}</div>
                                            <small class="text-muted">{{ $item->item_code }}</small>
                                        </td>
                                        <td>{{ $item->spk_number }}</td>
                                        <td>{{ number_format($item->qty, 0, ',', '.') }} {{ $item->uom }}</td>
                                        <td>
                                            <span class="badge rounded-pill text-bg-success">{{ $item->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
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
