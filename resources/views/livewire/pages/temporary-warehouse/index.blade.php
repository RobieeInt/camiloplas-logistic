<div>
    <style>
        .tw-camera-box { min-height: 320px; }
        #cameraScanner { width: 100%; min-height: 320px; }
        #cameraScanner video { border-radius: 1rem; object-fit: cover; }
        .tw-mobile-action { white-space: nowrap; }

        @media (max-width: 767.98px) {
            .tw-page-title h4 { font-size: 1.15rem; }

            .tw-page-actions {
                width: 100%;
                display: grid !important;
                grid-template-columns: 1fr 1fr;
            }

            .tw-page-actions .btn {
                width: 100%;
                padding-left: .75rem !important;
                padding-right: .75rem !important;
                font-size: .85rem;
            }

            .tw-summary-card .fs-3 { font-size: 1.5rem !important; }

            .tw-trolley-table th,
            .tw-trolley-table td {
                font-size: .85rem;
                white-space: nowrap;
            }

            .tw-camera-box { min-height: 260px; }
            #cameraScanner { min-height: 260px; }

            .tw-scan-modal .modal-body { padding: 1rem; }
            .tw-scan-info { padding: .85rem !important; }

            .tw-scan-input {
                font-size: 1.35rem !important;
                height: 58px;
            }

            .tw-mobile-stack {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: .5rem;
            }

            .tw-mobile-stack .btn { width: 100%; }

            .tw-action-stack {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: .35rem;
            }

            .tw-action-stack .btn,
            .tw-action-stack a {
                width: 100%;
            }
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="tw-page-title">
            <h4 class="fw-bold mb-1">Temporary Warehouse</h4>
            <div class="text-muted">Print barcode, pilih troli, scan dus, validasi, lalu kirim ke FGW.</div>
        </div>

        <div class="d-flex gap-2 tw-page-actions">
            <button type="button" class="btn btn-light border rounded-3 px-4" wire:click="createTrolley">
                <i class="bi bi-cart-plus me-1"></i>
                Buat Troli
            </button>

            <button type="button" class="btn btn-primary rounded-3 px-4" wire:click="openPrintModal">
                <i class="bi bi-printer me-1"></i>
                Print Barcode
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 tw-summary-card">
                <div class="card-body">
                    <div class="text-muted small">Printed Hari Ini</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_printed_today }}</div>
                    <div class="small text-muted">Dus barcode</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 tw-summary-card">
                <div class="card-body">
                    <div class="text-muted small">Ready Scan</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_ready_scan }}</div>
                    <div class="small text-muted">Belum masuk troli</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 tw-summary-card">
                <div class="card-body">
                    <div class="text-muted small">Troli Open</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_open_trolley }}</div>
                    <div class="small text-muted">Sedang diisi</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 tw-summary-card">
                <div class="card-body">
                    <div class="text-muted small">Kirim FGW</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_sent_fgw }}</div>
                    <div class="small text-muted">Troli terkirim</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Troli Aktif</h5>
            <div class="text-muted small">Pilih troli, scan barcode dus, cek detail isi, kunci troli, lalu kirim FGW.</div>
        </div>

        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tw-trolley-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Troli</th>
                            <th>Barcode</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($trolleys as $trolley)
                            @php
                                $percent = !is_null($trolley->capacity) && $trolley->capacity > 0
                                ? ($trolley->total_items / $trolley->capacity) * 100
                                : 0;
                            @endphp

                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $trolley->trolley_code }}</div>
                                    <small class="text-muted">
                                        Capacity
                                        {{ $trolley->capacity ? $trolley->capacity . ' dus' : '∞' }}
                                    </small>
                                </td>

                                <td><code>{{ $trolley->barcode }}</code></td>

                                <td style="min-width: 220px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>
                                            {{ $trolley->total_items }}/{{ $trolley->capacity ?? '∞' }} dus
                                        </span>
                                        <span>{{ number_format($percent, 0) }}%</span>
                                    </div>

                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: {{ $percent }}%"></div>
                                    </div>
                                </td>

                                <td>
                                    @if ($trolley->status === 'COMPLETE')
                                        <span class="badge rounded-pill text-bg-success">COMPLETE</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-primary">OPEN</span>
                                    @endif
                                </td>

                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1 tw-action-stack">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border rounded-3 tw-mobile-action"
                                            wire:click="openTrolleyDetailModal({{ $trolley->id }})"
                                        >
                                            <i class="bi bi-list-check me-1"></i>
                                            Detail
                                        </button>

                                        @if ($trolley->status === 'OPEN')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary rounded-3 tw-mobile-action"
                                                wire:click="openScanModal(
                                                    {{ $trolley->id }},
                                                    '{{ $trolley->trolley_code }}',
                                                    '{{ $trolley->barcode }}',
                                                   {{ $trolley->capacity ?? 'null' }},
                                                    {{ $trolley->total_items }}
                                                )"
                                            >
                                                <i class="bi bi-camera me-1"></i>
                                                Scan Dus
                                            </button>

                                            @if ($trolley->total_items > 0)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-warning rounded-3 tw-mobile-action"
                                                    onclick="confirmForceComplete({{ $trolley->id }})"
                                                >
                                                    <i class="bi bi-check2-circle me-1"></i>
                                                    Kunci Troli
                                                </button>
                                            @endif
                                        @endif

                                        <a
                                            href="{{ route('temporary-warehouse.print-trolley-qr', ['trolley_id' => $trolley->id]) }}"
                                            target="_blank"
                                            class="btn btn-sm btn-light border rounded-3 tw-mobile-action"
                                        >
                                            <i class="bi bi-qr-code me-1"></i>
                                            Print QR
                                        </a>

                                        @if ($trolley->status === 'COMPLETE')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success rounded-3 tw-mobile-action"
                                                onclick="confirmSendToFgw({{ $trolley->id }})"
                                            >
                                                <i class="bi bi-truck me-1"></i>
                                                Kirim FGW
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                    Belum ada troli aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="p-3 border-bottom bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control border-0 bg-light"
                                placeholder="Cari barcode, box, SPK, item..."
                            >
                        </div>
                    </div>

                    <div class="col-md-6 text-md-end text-muted small">
                        Total data: {{ $packingUnits->total() }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Label</th>
                            <th>SPK</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Printed</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($packingUnits as $unit)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $unit->box_number }}</div>
                                    <code>{{ $unit->barcode }}</code>
                                </td>

                                <td>
                                    <div>{{ $unit->spk_number }}</div>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($unit->production_date)->format('d/m/Y') }}
                                    </small>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $unit->item_name }}</div>
                                    <small class="text-muted">{{ $unit->item_code }}</small>
                                </td>

                                <td>{{ number_format($unit->qty, 0, ',', '.') }} {{ $unit->uom }}</td>

                                <td>
                                    @if ($unit->status === 'PRINTED')
                                        <span class="badge rounded-pill text-bg-light border">PRINTED</span>
                                    @elseif ($unit->status === 'SCANNED_TO_TROLLEY')
                                        <span class="badge rounded-pill text-bg-primary">IN TROLLEY</span>
                                    @elseif ($unit->status === 'SENT_FGW')
                                        <span class="badge rounded-pill text-bg-success">SENT FGW</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">{{ $unit->status }}</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="text-muted">
                                        {{ $unit->printed_at ? \Carbon\Carbon::parse($unit->printed_at)->format('d M Y H:i') : '-' }}
                                    </span>
                                </td>

                                <td class="text-end pe-4">
                                    <a
                                        href="{{ route('temporary-warehouse.print-labels', ['packing_unit_id' => $unit->id]) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-light border rounded-3"
                                    >
                                        <i class="bi bi-printer me-1"></i>
                                        Print Ulang
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Belum ada barcode dus.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-white">
                {{ $packingUnits->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Print Barcode --}}
    <div
        class="modal fade @if($showPrintModal) show d-block @endif"
        tabindex="-1"
        style="@if($showPrintModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Print Barcode Code Product</h5>
                        <div class="text-muted small">Generate barcode berdasarkan SPK produksi.</div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closePrintModal"></button>
                </div>

                <form wire:submit.prevent="printBarcode">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">SPK Produksi</label>

                            <select wire:model="productionOrderId" class="form-select rounded-3 @error('productionOrderId') is-invalid @enderror">
                                <option value="">Pilih SPK</option>
                                @foreach ($productionOrders as $po)
                                    <option value="{{ $po->id }}">
                                        {{ $po->spk_number }} — {{ $po->item_name }}
                                        @if ($po->so_number)
                                            [{{ $po->so_number }}]
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('productionOrderId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Jumlah Dus</label>
                                <input
                                    type="number"
                                    wire:model.live="totalBox"
                                    class="form-control rounded-3 @error('totalBox') is-invalid @enderror"
                                    min="1"
                                    max="500"
                                    placeholder="contoh: 10"
                                >
                                @error('totalBox')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-semibold">
                                    Qty per Dus
                                    <span class="text-muted fw-normal small">(PCS)</span>
                                </label>
                                <input
                                    type="number"
                                    wire:model.live="qtyPerBox"
                                    class="form-control rounded-3 @error('qtyPerBox') is-invalid @enderror"
                                    min="1"
                                    max="100000"
                                    placeholder="contoh: 1000"
                                >
                                @error('qtyPerBox')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Preview Kalkulasi --}}
                        @if ($totalBox > 0 && $qtyPerBox > 0)
                            <div class="rounded-3 bg-primary-subtle border border-primary-subtle p-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Jumlah barcode digenerate</span>
                                    <span class="fw-semibold">{{ number_format($totalBox) }} dus</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Isi per dus</span>
                                    <span class="fw-semibold">{{ number_format($qtyPerBox) }} PCS</span>
                                </div>
                                <div class="border-top border-primary-subtle mt-2 pt-2 d-flex justify-content-between">
                                    <span class="fw-semibold">Total PCS</span>
                                    <span class="fw-bold text-primary">{{ number_format($totalBox * $qtyPerBox) }} PCS</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" wire:click="closePrintModal" class="btn btn-light border rounded-3 px-4">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-printer me-1"></i>
                            Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Detail Troli --}}
    <div
        class="modal fade @if($showTrolleyDetailModal) show d-block @endif"
        tabindex="-1"
        style="@if($showTrolleyDetailModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Detail Isi Troli</h5>
                        <div class="text-muted small">
                            Cek isi troli sebelum dikirim. Karena salah scan itu bukan fitur.
                        </div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closeTrolleyDetailModal"></button>
                </div>

                <div class="modal-body">
                    @if (session('detail_success'))
                        <div class="alert alert-success border-0 rounded-3">{{ session('detail_success') }}</div>
                    @endif

                    @if (session('detail_error'))
                        <div class="alert alert-danger border-0 rounded-3">{{ session('detail_error') }}</div>
                    @endif

                    @if ($detailTrolley)
                        @php
                            $detailPercent = !is_null($detailTrolley->capacity) && $detailTrolley->capacity > 0
                                ? ($detailTrolley->total_items / $detailTrolley->capacity) * 100
                                : 0;
                        @endphp

                        <div class="border rounded-4 p-3 mb-3 bg-light">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="small text-muted">Troli</div>
                                    <div class="fw-bold fs-5">{{ $detailTrolley->trolley_code }}</div>
                                    <code>{{ $detailTrolley->barcode }}</code>
                                </div>

                                <div class="col-md-4">
                                    <div class="small text-muted">Status</div>
                                    <span class="badge rounded-pill {{ $detailTrolley->status === 'COMPLETE' ? 'text-bg-success' : 'text-bg-primary' }}">
                                        {{ $detailTrolley->status }}
                                    </span>
                                </div>

                                <div class="col-md-4">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Isi Troli</span>
                                        <span>
                                            {{ $detailTrolley->total_items }}/{{ $detailTrolley->capacity ?? '∞' }}
                                        </span>
                                    </div>

                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar" style="width: {{ $detailPercent }}%"></div>
                                    </div>
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
                                    <th>Scanned</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($detailTrolleyItems as $item)
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
                                            <div>{{ $item->scanned_by_name ?? '-' }}</div>
                                            <small class="text-muted">
                                                {{ $item->scanned_at ? \Carbon\Carbon::parse($item->scanned_at)->format('d M Y H:i') : '-' }}
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            @if ($detailTrolley && $detailTrolley->status === 'OPEN')
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light border text-danger rounded-3"
                                                    onclick="confirmRemoveDus({{ $detailTrolley->id }}, {{ $item->packing_unit_id }})"
                                                >
                                                    <i class="bi bi-x-circle me-1"></i>
                                                    Remove
                                                </button>
                                            @else
                                                <span class="text-muted small">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            Belum ada dus di troli ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" wire:click="closeTrolleyDetailModal" class="btn btn-light border rounded-3 px-4">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Scan Dus --}}
    <div
        class="modal fade tw-scan-modal @if($showScanModal) show d-block @endif"
        tabindex="-1"
        style="@if($showScanModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Scan Dus ke Troli</h5>
                        <div class="text-muted small">Kamera tetap standby setelah scan.</div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closeScanModal"></button>
                </div>

                <form wire:submit.prevent="scanDus">
                    <div class="modal-body">
                        @if (session('scan_success'))
                            <div class="alert alert-success border-0 rounded-3">
                                {{ session('scan_success') }}
                            </div>
                        @endif

                        @if (session('scan_error'))
                            <div class="alert alert-danger border-0 rounded-3">
                                {{ session('scan_error') }}
                            </div>
                        @endif

                        <div class="border rounded-4 p-3 mb-3 bg-light tw-scan-info">
                            <div class="small text-muted">Troli Dipilih</div>
                            <div class="fw-bold fs-5">{{ $selectedTrolleyCode }}</div>
                            <code>{{ $selectedTrolleyBarcode }}</code>

                            @php
                                $selectedPercent = !is_null($selectedTrolleyCapacity) && $selectedTrolleyCapacity > 0
                                    ? ($selectedTrolleyTotalItems / $selectedTrolleyCapacity) * 100
                                    : 0;
                            @endphp

                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Isi Troli</span>
                                    <span>{{ $selectedTrolleyTotalItems }}/{{ $selectedTrolleyCapacity ?? '∞' }}</span>
                                </div>

                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" style="width: {{ $selectedPercent }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div wire:ignore class="border rounded-4 overflow-hidden bg-dark tw-camera-box">
                                    <div id="cameraScanner"></div>
                                </div>

                                <div id="cameraScannerStatus" class="small text-muted mt-2">
                                    Menunggu kamera...
                                </div>

                                <div class="d-flex gap-2 mt-2 tw-mobile-stack">
                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="startCameraScanner()">
                                        <i class="bi bi-camera-video me-1"></i>
                                        Nyalakan Kamera
                                    </button>

                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="stopCameraScanner()">
                                        <i class="bi bi-camera-video-off me-1"></i>
                                        Matikan Kamera
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <label class="form-label fw-semibold">Barcode Dus</label>

                                <input
                                    type="text"
                                    id="packingBarcodeInput"
                                    wire:model.defer="packingBarcode"
                                    class="form-control form-control-lg rounded-3 text-center fw-bold tw-scan-input"
                                    placeholder="Scan barcode dus"
                                    autocomplete="off"
                                >

                                @error('packingBarcode')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                                <div class="form-text mb-3">
                                    Bisa kamera HP atau scanner gun.
                                </div>

                                <button type="submit" class="btn btn-primary rounded-3 px-4 w-100">
                                    <i class="bi bi-upc-scan me-1"></i>
                                    Masukkan ke Troli
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @script
    <script>
        let scannerInstance = null;
        let scannerReady = false;
        let scannerBusy = false;
        let scannerLastCode = '';
        let scannerLastTime = 0;

        function setScannerStatus(message, isError = false) {
            const status = document.getElementById('cameraScannerStatus');

            if (!status) return;

            status.innerText = message;
            status.classList.toggle('text-danger', isError);
            status.classList.toggle('text-muted', !isError);
        }

        function loadHtml5QrCodeScript() {
            return new Promise((resolve, reject) => {
                if (window.Html5Qrcode) {
                    resolve();
                    return;
                }

                const existingScript = document.getElementById('html5-qrcode-script');

                if (existingScript) {
                    existingScript.addEventListener('load', resolve);
                    existingScript.addEventListener('error', reject);
                    return;
                }

                const script = document.createElement('script');
                script.id = 'html5-qrcode-script';
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = resolve;
                script.onerror = reject;

                document.body.appendChild(script);
            });
        }

        window.focusScanInput = function () {
            setTimeout(() => {
                const input = document.getElementById('packingBarcodeInput');

                if (input) {
                    input.focus();
                    input.select();
                }
            }, 150);
        }

        window.resumeCameraScanner = function () {
            scannerBusy = false;

            try {
                if (scannerInstance && scannerReady) {
                    scannerInstance.resume();
                }
            } catch (error) {}

            setScannerStatus('Kamera standby. Arahkan ke barcode dus.');
            focusScanInput();
        }

        window.startCameraScanner = async function () {
            const scannerElement = document.getElementById('cameraScanner');

            if (!scannerElement) return;

            try {
                await loadHtml5QrCodeScript();

                if (scannerReady && scannerInstance) {
                    resumeCameraScanner();
                    return;
                }

                scannerElement.innerHTML = '';
                scannerInstance = new Html5Qrcode('cameraScanner');

                const config = {
                    fps: 12,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        const isMobile = window.innerWidth < 768;
                        const width = Math.floor(viewfinderWidth * (isMobile ? 0.92 : 0.86));
                        const height = Math.floor(viewfinderHeight * (isMobile ? 0.28 : 0.32));

                        return { width, height };
                    },
                    aspectRatio: window.innerWidth < 768 ? 1.333 : 1.777,
                    disableFlip: false,
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.CODE_128,
                        Html5QrcodeSupportedFormats.CODE_39,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.EAN_8,
                        Html5QrcodeSupportedFormats.UPC_A,
                        Html5QrcodeSupportedFormats.UPC_E
                    ]
                };

                await scannerInstance.start(
                    { facingMode: 'environment' },
                    config,
                    async function(decodedText) {
                        const cleanBarcode = String(decodedText).trim();
                        const now = Date.now();

                        if (!cleanBarcode) return;
                        if (scannerBusy) return;

                        if (scannerLastCode === cleanBarcode && (now - scannerLastTime) < 2500) {
                            return;
                        }

                        scannerBusy = true;
                        scannerLastCode = cleanBarcode;
                        scannerLastTime = now;

                        setScannerStatus('Barcode terbaca: ' + cleanBarcode + '. Menyimpan...');

                        try {
                            if (scannerInstance && scannerReady) {
                                scannerInstance.pause(true);
                            }
                        } catch (error) {}

                        const input = document.getElementById('packingBarcodeInput');

                        if (input) {
                            input.value = cleanBarcode;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        try {
                            await $wire.set('packingBarcode', cleanBarcode);
                            await $wire.scanDus();
                        } catch (error) {
                            setScannerStatus('Gagal mengirim scan ke server.', true);
                            setTimeout(() => resumeCameraScanner(), 800);
                        }
                    },
                    function() {}
                );

                scannerReady = true;
                scannerBusy = false;

                setScannerStatus('Kamera standby. Arahkan ke barcode dus.');
                focusScanInput();
            } catch (error) {
                scannerReady = false;
                scannerBusy = false;
                setScannerStatus('Kamera gagal dibuka. Pakai HTTPS atau localhost, lalu izinkan kamera.', true);
                focusScanInput();
            }
        }

        window.stopCameraScanner = async function () {
            try {
                if (scannerInstance && scannerReady) {
                    await scannerInstance.stop();
                    await scannerInstance.clear();
                }
            } catch (error) {}

            scannerInstance = null;
            scannerReady = false;
            scannerBusy = false;
            scannerLastCode = '';
            scannerLastTime = 0;

            const scannerElement = document.getElementById('cameraScanner');

            if (scannerElement) {
                scannerElement.innerHTML = '';
            }

            setScannerStatus('Kamera dimatikan.');
        }

        Livewire.on('scan-modal-opened', () => {
            setTimeout(() => {
                focusScanInput();
                startCameraScanner();
            }, 450);
        });

        Livewire.on('scan-ready-again', () => {
            setTimeout(() => {
                resumeCameraScanner();
            }, 450);
        });

        Livewire.on('scan-modal-closed', () => {
            stopCameraScanner();
        });

        window.confirmSendToFgw = function (id) {
            Swal.fire({
                title: 'Kirim troli ke FGW?',
                text: 'Troli complete akan dikirim ke Finish Goods Warehouse.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-success rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.sendToFgw(id);
                }
            });
        }

        window.confirmForceComplete = function (id) {
            Swal.fire({
                title: 'Kunci troli?',
                text: 'Troli akan dikunci & tidak bisa diubah lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kunci Troli',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-warning rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.forceCompleteTrolley(id);
                }
            });
        }

        window.confirmRemoveDus = function (trolleyId, packingUnitId) {
            Swal.fire({
                title: 'Keluarkan dus?',
                text: 'Status dus akan kembali menjadi PRINTED dan bisa discan ulang.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, keluarkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.removeDusFromTrolley(trolleyId, packingUnitId);
                }
            });
        }
    </script>
    @endscript
</div>
