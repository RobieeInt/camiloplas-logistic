<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Log Scan Dus</h4>
            <div class="text-muted">Scan barcode untuk lihat detail produksi, atau cari di tabel di bawah.</div>
        </div>
    </div>

    {{-- ── Scan bar ──────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form wire:submit.prevent="scan" class="d-flex gap-3 align-items-end flex-wrap">
                <div class="flex-grow-1">
                    <label class="form-label fw-semibold mb-1">Scan / Ketik Barcode Dus</label>
                    <input
                        type="text"
                        id="scanLogInput"
                        wire:model.defer="scanBarcode"
                        class="form-control form-control-lg rounded-3 @error('scanBarcode') is-invalid @enderror"
                        placeholder="Scan barcode atau ketik nomor barcode..."
                        autocomplete="off"
                    >
                    @error('scanBarcode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 px-4">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>

            @if (session('scan_error'))
                <div class="alert alert-danger border-0 rounded-3 mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('scan_error') }}
                </div>
            @endif
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-3">{{ session('error') }}</div>
    @endif

    {{-- ── Summary ───────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Dus</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary->total) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Belum Scan TW</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($summary->total_printed) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Scan TW</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($summary->total_tw_scanned) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Dalam Troli</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($summary->total_in_trolley) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Di FGW</div>
                    <div class="fs-4 fw-bold text-info">{{ number_format($summary->total_fgw) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Loaded</div>
                    <div class="fs-4 fw-bold text-dark">{{ number_format($summary->total_loaded) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Log table ─────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="p-3 border-bottom bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input
                                type="text"
                                wire:model.live.debounce.400ms="search"
                                class="form-control border-0 bg-light"
                                placeholder="Barcode, box, SPK, item, batch..."
                            >
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select wire:model.live="filterStatus" class="form-select border-0 bg-light">
                            <option value="">Semua Status</option>
                            <option value="PRINTED">PRINTED</option>
                            <option value="TW_SCANNED">TW SCANNED</option>
                            <option value="SCANNED_TO_TROLLEY">IN TROLLEY</option>
                            <option value="SENT_FGW">SENT FGW</option>
                            <option value="RECEIVED_FGW">RECEIVED FGW</option>
                            <option value="LOADED">LOADED</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select wire:model.live="filterDate" class="form-select border-0 bg-light">
                            <option value="">Semua Tanggal</option>
                            <option value="today">Hari Ini</option>
                            <option value="week">7 Hari Terakhir</option>
                            <option value="month">30 Hari Terakhir</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2 align-items-center">
                        @if ($search || $filterStatus || $filterDate)
                            <button wire:click="resetFilters" class="btn btn-sm btn-light border rounded-3">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </button>
                        @endif
                        <span class="text-muted small ms-auto">{{ number_format($logs->total()) }} data</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Box / Barcode</th>
                            <th>Item</th>
                            <th>SPK</th>
                            <th>Batch / LOT</th>
                            <th>Produksi</th>
                            <th>QC</th>
                            <th>Status</th>
                            <th>Print</th>
                            <th>TW Scan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $log->box_number }}</div>
                                    <code class="text-muted">{{ $log->barcode }}</code>
                                    <div class="text-muted">{{ number_format($log->qty) }} {{ $log->uom }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $log->item_name }}</div>
                                    <small class="text-muted">{{ $log->item_code }}</small>
                                </td>
                                <td>
                                    <div>{{ $log->spk_number }}</div>
                                    @if ($log->factory)
                                        <small class="text-muted">{{ $log->factory }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->batch_number)
                                        <div class="fw-semibold text-primary small">{{ $log->batch_number }}</div>
                                        @if ($log->lot_number)
                                            <small class="text-muted">LOT: {{ $log->lot_number }}</small>
                                        @endif
                                        @if ($log->berat)
                                            <div class="text-muted small">{{ $log->berat }} kg</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->operator)
                                        <div>{{ $log->operator }}</div>
                                        <small class="text-muted">{{ $log->mesin_kode }} · {{ $log->shift }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $log->qc_operator ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        [$bg, $extra, $label] = match($log->status) {
                                            'PRINTED'            => ['warning',   'text-dark', 'PRINTED'],
                                            'TW_SCANNED'         => ['success',   '',          'TW SCANNED'],
                                            'SCANNED_TO_TROLLEY' => ['primary',   '',          'IN TROLLEY'],
                                            'SENT_FGW'           => ['info',      'text-dark', 'SENT FGW'],
                                            'RECEIVED_FGW'       => ['secondary', '',          'RECEIVED FGW'],
                                            'LOADED'             => ['dark',      '',          'LOADED'],
                                            default              => ['secondary', '',          $log->status],
                                        };
                                    @endphp
                                    <span class="badge rounded-pill text-bg-{{ $bg }} {{ $extra }}">{{ $label }}</span>
                                </td>
                                <td>
                                    @if ($log->printed_at)
                                        <div class="small">{{ \Carbon\Carbon::parse($log->printed_at)->format('d/m/Y H:i') }}</div>
                                        @if ($log->printed_by_name)
                                            <small class="text-muted">{{ $log->printed_by_name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->prod_scanned_at)
                                        <div class="small fw-semibold text-success">{{ \Carbon\Carbon::parse($log->prod_scanned_at)->format('d/m/Y H:i') }}</div>
                                        @if ($log->tw_scanned_by_name)
                                            <small class="text-muted">{{ $log->tw_scanned_by_name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Tidak ada data scan ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-white">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         MODAL DETAIL — muncul setelah scan
    ══════════════════════════════════════════════════════════════════ --}}
    <div
        class="modal fade @if($showDetailModal) show d-block @endif"
        tabindex="-1"
        style="@if($showDetailModal) background: rgba(15,23,42,.55); overflow: hidden; @else display:none; @endif"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4" style="max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">

                @if ($detail)
                @php
                    $pu      = $detail['packing_unit'];
                    $batches = $detail['batches'] ?? [];
                    $spk     = $detail['spk'];
                    $hasBom  = !empty($detail['bom_items']);
                    $hasRuns = !empty($detail['runs']);

                    $statusBadge = match($pu['status']) {
                        'PRINTED'            => ['warning',   'text-dark', 'PRINTED'],
                        'TW_SCANNED'         => ['success',   '',          'TW SCANNED'],
                        'SCANNED_TO_TROLLEY' => ['primary',   '',          'IN TROLLEY'],
                        'SENT_FGW'           => ['info',      'text-dark', 'SENT FGW'],
                        'RECEIVED_FGW'       => ['secondary', '',          'RECEIVED FGW'],
                        'LOADED'             => ['dark',      '',          'LOADED'],
                        default              => ['secondary', '',          $pu['status']],
                    };
                @endphp

                <div class="modal-header border-0 pb-1">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0">{{ $pu['box_number'] }}</h5>
                            <span class="badge text-bg-{{ $statusBadge[0] }} {{ $statusBadge[1] }} rounded-pill">{{ $statusBadge[2] }}</span>
                        </div>
                        <div class="text-muted small mt-1">
                            <code>{{ $pu['barcode'] }}</code>
                            &nbsp;·&nbsp; {{ $pu['item_name'] }} ({{ $pu['item_code'] }})
                            &nbsp;·&nbsp; {{ number_format($pu['qty']) }} {{ $pu['uom'] }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeDetailModal"></button>
                </div>

                <div class="modal-body pt-2" style="overflow-y: auto; flex: 1 1 auto;">
                    <div class="row g-3">

                        {{-- ── Batch & Produksi ── --}}
                        <div class="col-md-6">
                            <div class="card border rounded-3 h-100">
                                <div class="card-header bg-transparent border-bottom py-2 fw-semibold small text-muted text-uppercase d-flex align-items-center gap-2">
                                    <span><i class="bi bi-boxes me-1"></i> Batch & Produksi</span>
                                    @if (!empty($batches))
                                        <span class="badge text-bg-primary rounded-pill fw-normal" style="font-size:.7rem">{{ count($batches) }}</span>
                                    @endif
                                    @if ($detail['batch_via_spk'])
                                        <span class="badge text-bg-secondary rounded-pill fw-normal" style="font-size:.7rem">via SPK</span>
                                    @endif
                                </div>
                                <div class="card-body py-3" x-data="{ open: false }">
                                    @if (!empty($batches))
                                        @foreach ($batches as $bIdx => $b)
                                            @if ($bIdx === 0)
                                                <dl class="row mb-0 small">
                                                    <dt class="col-5 text-muted">Batch</dt>
                                                    <dd class="col-7 fw-semibold text-primary mb-1">{{ $b['batch_number'] ?? '—' }}</dd>

                                                    <dt class="col-5 text-muted">LOT</dt>
                                                    <dd class="col-7 mb-1">{{ $b['lot_number'] ?? '—' }}</dd>

                                                    <dt class="col-5 text-muted">Berat</dt>
                                                    <dd class="col-7 mb-1">{{ $b['berat'] ? number_format($b['berat'], 2) . ' kg' : '—' }}</dd>

                                                    <dt class="col-5 text-muted">QC Inspector</dt>
                                                    <dd class="col-7 mb-1">{{ $b['qc_operator'] ?? '—' }}</dd>

                                                    <dt class="col-5 text-muted">Status Batch</dt>
                                                    <dd class="col-7 mb-1">
                                                        <span class="badge text-bg-{{ ($b['status'] ?? '') === 'picked_up' ? 'success' : 'secondary' }} rounded-pill">
                                                            {{ strtoupper($b['status'] ?? '—') }}
                                                        </span>
                                                    </dd>

                                                    @if (!empty($b['qc_printed_at']))
                                                        <dt class="col-5 text-muted">QC Printed</dt>
                                                        <dd class="col-7 mb-1">{{ \Carbon\Carbon::parse($b['qc_printed_at'])->format('d/m/Y H:i') }}</dd>
                                                    @endif

                                                    @if (!empty($b['operator']) || !empty($b['mesin_kode']) || !empty($b['shift']))
                                                        <dt class="col-5 text-muted">Operator</dt>
                                                        <dd class="col-7 mb-1">{{ $b['operator'] ?? '—' }}</dd>

                                                        <dt class="col-5 text-muted">Mesin</dt>
                                                        <dd class="col-7 mb-1">{{ $b['mesin_kode'] ?? '—' }}</dd>

                                                        <dt class="col-5 text-muted">Shift</dt>
                                                        <dd class="col-7 mb-0">{{ $b['shift'] ?? '—' }}</dd>
                                                    @endif
                                                </dl>
                                            @else
                                                <div x-show="open" style="display:none">
                                                    <hr class="my-2">
                                                    <dl class="row mb-0 small">
                                                        <dt class="col-5 text-muted">Batch</dt>
                                                        <dd class="col-7 fw-semibold text-primary mb-1">{{ $b['batch_number'] ?? '—' }}</dd>

                                                        <dt class="col-5 text-muted">LOT</dt>
                                                        <dd class="col-7 mb-1">{{ $b['lot_number'] ?? '—' }}</dd>

                                                        <dt class="col-5 text-muted">Berat</dt>
                                                        <dd class="col-7 mb-1">{{ $b['berat'] ? number_format($b['berat'], 2) . ' kg' : '—' }}</dd>

                                                        <dt class="col-5 text-muted">QC Inspector</dt>
                                                        <dd class="col-7 mb-1">{{ $b['qc_operator'] ?? '—' }}</dd>

                                                        <dt class="col-5 text-muted">Status Batch</dt>
                                                        <dd class="col-7 mb-1">
                                                            <span class="badge text-bg-{{ ($b['status'] ?? '') === 'picked_up' ? 'success' : 'secondary' }} rounded-pill">
                                                                {{ strtoupper($b['status'] ?? '—') }}
                                                            </span>
                                                        </dd>

                                                        @if (!empty($b['qc_printed_at']))
                                                            <dt class="col-5 text-muted">QC Printed</dt>
                                                            <dd class="col-7 mb-1">{{ \Carbon\Carbon::parse($b['qc_printed_at'])->format('d/m/Y H:i') }}</dd>
                                                        @endif

                                                        @if (!empty($b['operator']) || !empty($b['mesin_kode']) || !empty($b['shift']))
                                                            <dt class="col-5 text-muted">Operator</dt>
                                                            <dd class="col-7 mb-1">{{ $b['operator'] ?? '—' }}</dd>

                                                            <dt class="col-5 text-muted">Mesin</dt>
                                                            <dd class="col-7 mb-1">{{ $b['mesin_kode'] ?? '—' }}</dd>

                                                            <dt class="col-5 text-muted">Shift</dt>
                                                            <dd class="col-7 mb-0">{{ $b['shift'] ?? '—' }}</dd>
                                                        @endif
                                                    </dl>
                                                </div>
                                            @endif
                                        @endforeach

                                        @if (count($batches) > 1)
                                            <div class="mt-3">
                                                <button
                                                    type="button"
                                                    x-on:click="open = !open"
                                                    class="btn btn-sm btn-outline-secondary rounded-3 w-100"
                                                >
                                                    <span x-show="!open"><i class="bi bi-chevron-down me-1"></i> Lihat semua {{ count($batches) }} batch</span>
                                                    <span x-show="open" style="display:none"><i class="bi bi-chevron-up me-1"></i> Sembunyikan batch lainnya</span>
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted small py-2">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Belum ada data batch untuk SPK ini di <code>batch_pickup_log</code>.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ── SPK ── --}}
                        <div class="col-md-6">
                            <div class="card border rounded-3 h-100">
                                <div class="card-header bg-transparent border-bottom py-2 fw-semibold small text-muted text-uppercase">
                                    <i class="bi bi-file-earmark-text me-1"></i> SPK Produksi
                                </div>
                                <div class="card-body py-3">
                                    @if ($spk)
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">SPK Number</dt>
                                            <dd class="col-7 fw-semibold mb-1">{{ $spk['spk_number'] }}</dd>

                                            <dt class="col-5 text-muted">Type</dt>
                                            <dd class="col-7 mb-1">{{ $spk['type'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Pabrik</dt>
                                            <dd class="col-7 mb-1">{{ $spk['factory'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Departemen</dt>
                                            <dd class="col-7 mb-1">{{ $spk['department'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Produk</dt>
                                            <dd class="col-7 mb-1">{{ $spk['product'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Qty SPK</dt>
                                            <dd class="col-7 mb-1">{{ $spk['qty'] ? number_format($spk['qty']) : '—' }}</dd>

                                            <dt class="col-5 text-muted">Mesin</dt>
                                            <dd class="col-7 mb-1">{{ $spk['mesin'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Ref SO</dt>
                                            <dd class="col-7 mb-1">{{ $spk['ref_so'] ?? '—' }}</dd>

                                            <dt class="col-5 text-muted">Status</dt>
                                            <dd class="col-7 mb-1">
                                                <span class="badge text-bg-{{ $spk['status'] === 'Selesai' ? 'success' : 'warning' }} rounded-pill text-dark">
                                                    {{ $spk['status'] ?? '—' }}
                                                </span>
                                            </dd>

                                            @if ($spk['delivery_date'])
                                                <dt class="col-5 text-muted">Delivery</dt>
                                                <dd class="col-7 mb-0">{{ \Carbon\Carbon::parse($spk['delivery_date'])->format('d/m/Y') }}</dd>
                                            @endif
                                        </dl>

                                        @if ($hasBom)
                                            <div class="mt-3">
                                                <button
                                                    type="button"
                                                    wire:click="openBomModal"
                                                    class="btn btn-sm btn-outline-primary rounded-3"
                                                >
                                                    <i class="bi bi-list-ul me-1"></i>
                                                    Lihat BOM Items ({{ count($detail['bom_items']) }})
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted small py-2">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Data SPK tidak ditemukan.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ── Alur Logistik ── --}}
                        @php
                            $lg   = $detail['logistics'] ?? [];
                            $stat = $pu['status'];
                            $steps = [
                                'PRINTED'            => 1,
                                'TW_SCANNED'         => 2,
                                'SCANNED_TO_TROLLEY' => 3,
                                'SENT_FGW'           => 4,
                                'RECEIVED_FGW'       => 5,
                                'LOADED'             => 6,
                            ];
                            $currentStep = $steps[$stat] ?? 0;
                        @endphp
                        <div class="col-12">
                            <div class="card border rounded-3">
                                <div class="card-header bg-transparent border-bottom py-2 fw-semibold small text-muted text-uppercase">
                                    <i class="bi bi-signpost-split me-1"></i> Alur Scan & Pengiriman
                                </div>
                                <div class="card-body py-3">
                                    <div class="row g-2 align-items-stretch">

                                        {{-- 1. Print --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ $currentStep >= 1 ? 'border-success bg-success bg-opacity-10' : 'bg-light text-muted' }}">
                                                <div class="fw-semibold small mb-1 {{ $currentStep >= 1 ? 'text-success' : 'text-muted' }}">
                                                    <i class="bi bi-printer me-1"></i> Print
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['printed_by']))
                                                        <div class="text-muted" style="font-size:.75rem">Oleh</div>
                                                        <div class="fw-semibold">{{ $lg['printed_by'] }}</div>
                                                    @endif
                                                    @if (!empty($pu['printed_at']))
                                                        <div class="text-muted mt-1" style="font-size:.72rem">{{ \Carbon\Carbon::parse($pu['printed_at'])->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                    @if (empty($lg['printed_by']) && empty($pu['printed_at']))
                                                        <span class="text-muted" style="font-size:.75rem">—</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 2. TW Scan --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ $currentStep >= 2 ? 'border-success bg-success bg-opacity-10' : 'bg-light' }}">
                                                <div class="fw-semibold small mb-1 {{ $currentStep >= 2 ? 'text-success' : 'text-muted' }}">
                                                    <i class="bi bi-upc-scan me-1"></i> TW Scan
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['tw_scanned_by']))
                                                        <div class="text-muted" style="font-size:.75rem">Oleh</div>
                                                        <div class="fw-semibold">{{ $lg['tw_scanned_by'] }}</div>
                                                    @endif
                                                    @if (!empty($lg['tw_scanned_at']))
                                                        <div class="text-muted mt-1" style="font-size:.72rem">{{ \Carbon\Carbon::parse($lg['tw_scanned_at'])->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                    @if (empty($lg['tw_scanned_by']) && empty($lg['tw_scanned_at']))
                                                        <span class="text-muted" style="font-size:.75rem">Belum</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 3. TW QC → Troli --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ $currentStep >= 3 ? 'border-success bg-success bg-opacity-10' : 'bg-light' }}">
                                                <div class="fw-semibold small mb-1 {{ $currentStep >= 3 ? 'text-success' : 'text-muted' }}">
                                                    <i class="bi bi-cart-check me-1"></i> TW QC → Troli
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['trolley_code']))
                                                        <div class="text-muted" style="font-size:.75rem">Troli</div>
                                                        <div class="fw-semibold">{{ $lg['trolley_code'] }}</div>
                                                    @endif
                                                    @if (!empty($lg['trolley_scanned_by']))
                                                        <div class="text-muted mt-1" style="font-size:.75rem">Oleh</div>
                                                        <div>{{ $lg['trolley_scanned_by'] }}</div>
                                                    @endif
                                                    @if (!empty($lg['trolley_scanned_at']))
                                                        <div class="text-muted mt-1" style="font-size:.72rem">{{ \Carbon\Carbon::parse($lg['trolley_scanned_at'])->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                    @if (empty($lg['trolley_code']))
                                                        <span class="text-muted" style="font-size:.75rem">Belum</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 4. FGW Received + Rak --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ $currentStep >= 5 ? 'border-success bg-success bg-opacity-10' : 'bg-light' }}">
                                                <div class="fw-semibold small mb-1 {{ $currentStep >= 5 ? 'text-success' : 'text-muted' }}">
                                                    <i class="bi bi-building me-1"></i> FGW
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['rack_code']))
                                                        <div class="text-muted" style="font-size:.75rem">Rak</div>
                                                        <div class="fw-semibold">{{ $lg['rack_code'] }}@if(!empty($lg['rack_name'])) <span class="text-muted fw-normal">· {{ $lg['rack_name'] }}</span>@endif</div>
                                                    @endif
                                                    @if (!empty($lg['fgw_received_by']))
                                                        <div class="text-muted mt-1" style="font-size:.75rem">Diterima</div>
                                                        <div>{{ $lg['fgw_received_by'] }}</div>
                                                    @endif
                                                    @if (!empty($lg['received_fgw_at']))
                                                        <div class="text-muted mt-1" style="font-size:.72rem">{{ \Carbon\Carbon::parse($lg['received_fgw_at'])->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                    @if (empty($lg['rack_code']) && empty($lg['fgw_received_by']))
                                                        <span class="text-muted" style="font-size:.75rem">Belum</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 5. Loading --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ $currentStep >= 6 ? 'border-success bg-success bg-opacity-10' : 'bg-light' }}">
                                                <div class="fw-semibold small mb-1 {{ $currentStep >= 6 ? 'text-success' : 'text-muted' }}">
                                                    <i class="bi bi-truck me-1"></i> Loading
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['loaded_by']))
                                                        <div class="text-muted" style="font-size:.75rem">Oleh</div>
                                                        <div class="fw-semibold">{{ $lg['loaded_by'] }}</div>
                                                    @endif
                                                    @if (!empty($lg['loaded_at']))
                                                        <div class="text-muted mt-1" style="font-size:.72rem">{{ \Carbon\Carbon::parse($lg['loaded_at'])->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                    @if (empty($lg['loaded_by']))
                                                        <span class="text-muted" style="font-size:.75rem">Belum</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 6. DO & Surat Jalan --}}
                                        <div class="col-6 col-md-4 col-lg-2">
                                            <div class="border rounded-3 p-2 h-100 {{ !empty($lg['do_number']) ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' }}">
                                                <div class="fw-semibold small mb-1 {{ !empty($lg['do_number']) ? 'text-primary' : 'text-muted' }}">
                                                    <i class="bi bi-file-earmark-text me-1"></i> DO & Surat Jalan
                                                </div>
                                                <div class="small">
                                                    @if (!empty($lg['do_number']))
                                                        <div class="text-muted" style="font-size:.75rem">No. DO</div>
                                                        <div class="fw-semibold">{{ $lg['do_number'] }}</div>
                                                        @if (!empty($lg['customer_name']))
                                                            <div class="text-muted mt-1" style="font-size:.75rem">Customer</div>
                                                            <div>{{ $lg['customer_name'] }}</div>
                                                        @endif
                                                        @if (!empty($lg['customer_address']))
                                                            <div class="text-muted" style="font-size:.72rem">{{ $lg['customer_address'] }}</div>
                                                        @endif
                                                        @if (!empty($lg['truck_number']))
                                                            <div class="text-muted mt-1" style="font-size:.75rem">Kendaraan</div>
                                                            <div>{{ $lg['truck_number'] }}@if(!empty($lg['driver_name'])) · {{ $lg['driver_name'] }}@endif</div>
                                                        @endif
                                                        @if (!empty($lg['surat_jalan_first_printed_at']))
                                                            <div class="text-muted mt-1" style="font-size:.72rem">
                                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                                Surat jalan dicetak {{ \Carbon\Carbon::parse($lg['surat_jalan_first_printed_at'])->format('d/m/Y H:i') }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted" style="font-size:.75rem">Belum</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ── Production Runs ── --}}
                        @if ($hasRuns)
                        <div class="col-12">
                            <div class="card border rounded-3">
                                <div class="card-header bg-transparent border-bottom py-2 fw-semibold small text-muted text-uppercase">
                                    <i class="bi bi-play-circle me-1"></i> Production Runs ({{ count($detail['runs']) }})
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Run Number</th>
                                                <th>Mesin</th>
                                                <th>Operator</th>
                                                <th>Pabrik</th>
                                                <th class="text-end">Qty Target</th>
                                                <th class="text-end">Qty OK</th>
                                                <th class="text-end">Reject</th>
                                                <th>Status</th>
                                                <th>Mulai</th>
                                                <th>Selesai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($detail['runs'] as $run)
                                            <tr>
                                                <td class="ps-3 fw-semibold">{{ $run['run_number'] }}</td>
                                                <td>
                                                    <div>{{ $run['mesin_kode'] }}</div>
                                                    <small class="text-muted">{{ $run['mesin_nama'] }}</small>
                                                </td>
                                                <td>{{ $run['operator'] }}</td>
                                                <td>{{ $run['factory'] }}</td>
                                                <td class="text-end">{{ number_format($run['qty_target'], 2) }}</td>
                                                <td class="text-end text-success fw-semibold">{{ number_format($run['qty_ok'], 2) }}</td>
                                                <td class="text-end {{ $run['qty_reject'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                                    {{ number_format($run['qty_reject']) }}
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill text-bg-{{ $run['status'] === 'completed' ? 'success' : ($run['status'] === 'running' ? 'warning text-dark' : 'secondary') }}">
                                                        {{ $run['status'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ $run['started_at'] ? \Carbon\Carbon::parse($run['started_at'])->format('d/m/Y H:i') : '—' }}
                                                </td>
                                                <td>
                                                    {{ $run['completed_at'] ? \Carbon\Carbon::parse($run['completed_at'])->format('d/m/Y H:i') : '—' }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" wire:click="closeDetailModal" class="btn btn-light border rounded-3 px-4">
                        Tutup
                    </button>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         MODAL BOM ITEMS
    ══════════════════════════════════════════════════════════════════ --}}
    <div
        class="modal fade @if($showBomModal) show d-block @endif"
        tabindex="-1"
        style="@if($showBomModal) background: rgba(15,23,42,.7); z-index:1060; @else display:none; @endif"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">BOM Items</h5>
                        @if ($detail && $detail['spk'])
                            <div class="text-muted small">SPK: {{ $detail['spk']['spk_number'] }}</div>
                        @endif
                    </div>
                    <button type="button" class="btn-close" wire:click="closeBomModal"></button>
                </div>

                <div class="modal-body">
                    @if (!empty($bomItems))
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Material</th>
                                        <th class="text-end">Kebutuhan</th>
                                        <th>Satuan</th>
                                        <th class="text-end">Stok Tersedia</th>
                                        <th class="text-end">Requested</th>
                                        <th class="text-end">Issued</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bomItems as $bom)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $bom['material'] }}</td>
                                        <td class="text-end">{{ number_format($bom['kebutuhan'], 2) }}</td>
                                        <td>{{ $bom['satuan'] }}</td>
                                        <td class="text-end {{ $bom['stok_tersedia'] >= $bom['kebutuhan'] ? 'text-success' : 'text-danger' }} fw-semibold">
                                            {{ number_format($bom['stok_tersedia'], 2) }}
                                        </td>
                                        <td class="text-end text-muted">{{ number_format($bom['requested_qty'], 2) }}</td>
                                        <td class="text-end text-muted">{{ number_format($bom['issued_qty'], 2) }}</td>
                                        <td>
                                            @if ($bom['stok_tersedia'] < $bom['kebutuhan'])
                                                <span class="badge text-bg-danger rounded-pill">Stok Kurang</span>
                                            @else
                                                <span class="badge text-bg-success rounded-pill">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Tidak ada BOM items untuk SPK ini.
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" wire:click="closeBomModal" class="btn btn-light border rounded-3 px-4">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        window.scanLogFocus = function () {
            const el = document.getElementById('scanLogInput');
            if (el) el.focus();
        };

        Livewire.on('scan-log-focus-input', () => {
            setTimeout(() => scanLogFocus(), 200);
        });

        document.addEventListener('livewire:navigated', () => {
            setTimeout(() => scanLogFocus(), 300);
        });
    </script>
    @endscript
</div>
