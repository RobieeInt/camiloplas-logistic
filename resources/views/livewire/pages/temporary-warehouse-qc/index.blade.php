<div>
    <style>
        .qc-camera-box { min-height: 320px; }
        #qcCameraScanner { width: 100%; min-height: 320px; }
        #qcCameraScanner video { border-radius: 1rem; object-fit: cover; }

        @media (max-width: 767.98px) {
            .qc-camera-box { min-height: 260px; }
            #qcCameraScanner { min-height: 260px; }

            .qc-scan-input {
                font-size: 1.35rem !important;
                height: 58px;
            }

            .qc-action-stack {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: .35rem;
            }

            .qc-action-stack .btn,
            .qc-action-stack a {
                width: 100%;
            }
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Temporary Warehouse QC</h4>
            <div class="text-muted">Buat troli per item, scan barcode dus ke troli, lalu kirim ke FGW.</div>
        </div>

        <div>
            <button type="button" class="btn btn-primary rounded-3 px-4" wire:click="openCreateTrolleyModal">
                <i class="bi bi-cart-plus me-1"></i>
                Buat Troli
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
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Sudah Scan TW</div>
                    <div class="fs-3 fw-bold text-success">{{ $summary->total_tw_scanned }}</div>
                    <div class="small text-muted">Siap masuk troli</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Troli Open</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_open_trolley }}</div>
                    <div class="small text-muted">Sedang diisi</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Troli Complete</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_complete_trolley }}</div>
                    <div class="small text-muted">Siap kirim FGW</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
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
            <div class="text-muted small">Setiap troli hanya boleh diisi oleh satu jenis item.</div>
        </div>

        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Troli</th>
                            <th>Item</th>
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
                                        Kapasitas: {{ $trolley->capacity ? $trolley->capacity . ' dus' : '∞' }}
                                    </small>
                                </td>

                                <td>
                                    @if ($trolley->item_name)
                                        <div class="fw-semibold">{{ $trolley->item_name }}</div>
                                        <small class="text-muted">{{ $trolley->item_code }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td><code>{{ $trolley->barcode }}</code></td>

                                <td style="min-width: 200px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $trolley->total_items }}/{{ $trolley->capacity ?? '∞' }} dus</span>
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
                                    <div class="d-flex justify-content-end gap-1 qc-action-stack">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border rounded-3"
                                            wire:click="openTrolleyDetailModal({{ $trolley->id }})"
                                        >
                                            <i class="bi bi-list-check me-1"></i>
                                            Detail
                                        </button>

                                        @if ($trolley->status === 'OPEN')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary rounded-3"
                                                wire:click="openScanModal(
                                                    {{ $trolley->id }},
                                                    '{{ $trolley->trolley_code }}',
                                                    '{{ $trolley->barcode }}',
                                                    {{ $trolley->capacity ?? 'null' }},
                                                    {{ $trolley->total_items }},
                                                    '{{ addslashes($trolley->item_name ?? '') }}'
                                                )"
                                            >
                                                <i class="bi bi-camera me-1"></i>
                                                Scan Dus
                                            </button>

                                            @if ($trolley->total_items > 0)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-warning rounded-3"
                                                    onclick="qcConfirmForceComplete({{ $trolley->id }})"
                                                >
                                                    <i class="bi bi-check2-circle me-1"></i>
                                                    Kunci
                                                </button>
                                            @endif
                                        @endif

                                        @if ($trolley->status === 'COMPLETE')
                                            <a
                                                href="{{ route('temporary-warehouse.print-trolley-qr', ['trolley_id' => $trolley->id]) }}"
                                                target="_blank"
                                                class="btn btn-sm btn-light border rounded-3"
                                            >
                                                <i class="bi bi-qr-code me-1"></i>
                                                QR
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success rounded-3"
                                                onclick="qcConfirmSendToFgw({{ $trolley->id }})"
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
                                <td colspan="6" class="text-center text-muted py-5">
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

    {{-- Modal Buat Troli --}}
    <div
        class="modal fade @if($showCreateTrolleyModal) show d-block @endif"
        tabindex="-1"
        style="@if($showCreateTrolleyModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Buat Troli Baru</h5>
                        <div class="text-muted small">Pilih item — troli hanya boleh diisi satu jenis item.</div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeCreateTrolleyModal"></button>
                </div>

                <form wire:submit.prevent="submitCreateTrolley">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                            <select wire:model="createTrolleyItemId" class="form-select rounded-3 @error('createTrolleyItemId') is-invalid @enderror">
                                <option value="">Pilih Item</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} — {{ $item->item_name }}</option>
                                @endforeach
                            </select>
                            @error('createTrolleyItemId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Kapasitas
                                <span class="text-muted fw-normal small">(kosongkan = tidak terbatas)</span>
                            </label>
                            <input
                                type="number"
                                wire:model="createTrolleyCapacity"
                                class="form-control rounded-3"
                                min="1"
                                placeholder="contoh: 20"
                            >
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" wire:click="closeCreateTrolleyModal" class="btn btn-light border rounded-3 px-4">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-cart-plus me-1"></i>
                            Buat Troli
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
                                        <span>{{ $detailTrolley->total_items }}/{{ $detailTrolley->capacity ?? '∞' }}</span>
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
                                    <th>
                                        QC Scan By
                                        <small class="text-muted fw-normal d-block" style="font-size:.7rem;">prod_qc_scanned_by</small>
                                    </th>
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
                                            <div class="fw-semibold small">{{ $item->scanned_by_name ?? '-' }}</div>
                                            <small class="text-muted">
                                                {{ $item->scanned_at ? \Carbon\Carbon::parse($item->scanned_at)->format('d M Y H:i') : '-' }}
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            @if ($detailTrolley && $detailTrolley->status === 'OPEN')
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light border text-danger rounded-3"
                                                    onclick="qcConfirmRemoveDus({{ $detailTrolley->id }}, {{ $item->packing_unit_id }})"
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
        class="modal fade @if($showScanModal) show d-block @endif"
        tabindex="-1"
        style="@if($showScanModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Scan Dus ke Troli</h5>
                        <div class="text-muted small">
                            Khusus item: <strong>{{ $selectedTrolleyItemName ?: '-' }}</strong>
                            &nbsp;|&nbsp;Hanya dus status <span class="badge text-bg-success">TW SCANNED</span> yang boleh masuk.
                        </div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeScanModal"></button>
                </div>

                <form wire:submit.prevent="scanDus">
                    <div class="modal-body">
                        @if (session('scan_success'))
                            <div class="alert alert-success border-0 rounded-3">{{ session('scan_success') }}</div>
                        @endif
                        @if (session('scan_error'))
                            <div class="alert alert-danger border-0 rounded-3">{{ session('scan_error') }}</div>
                        @endif

                        <div class="border rounded-4 p-3 mb-3 bg-light">
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
                                <div wire:ignore class="border rounded-4 overflow-hidden bg-dark qc-camera-box">
                                    <div id="qcCameraScanner"></div>
                                </div>

                                <div id="qcCameraScannerStatus" class="small text-muted mt-2">
                                    Menunggu kamera...
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="qcStartCamera()">
                                        <i class="bi bi-camera-video me-1"></i>
                                        Nyalakan Kamera
                                    </button>

                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="qcStopCamera()">
                                        <i class="bi bi-camera-video-off me-1"></i>
                                        Matikan Kamera
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <label class="form-label fw-semibold">Barcode Dus</label>

                                <input
                                    type="text"
                                    id="qcPackingBarcodeInput"
                                    wire:model.defer="packingBarcode"
                                    class="form-control form-control-lg rounded-3 text-center fw-bold qc-scan-input"
                                    placeholder="Scan barcode dus"
                                    autocomplete="off"
                                >

                                @error('packingBarcode')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                                <div class="form-text mb-3">Bisa kamera HP atau scanner gun.</div>

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
        let qcScannerInstance = null;
        let qcScannerReady = false;
        let qcScannerBusy = false;
        let qcScannerLastCode = '';
        let qcScannerLastTime = 0;

        function qcSetStatus(msg, isError = false) {
            const el = document.getElementById('qcCameraScannerStatus');
            if (!el) return;
            el.innerText = msg;
            el.classList.toggle('text-danger', isError);
            el.classList.toggle('text-muted', !isError);
        }

        function loadQcScript() {
            return new Promise((resolve, reject) => {
                if (window.Html5Qrcode) { resolve(); return; }
                const existing = document.getElementById('html5-qrcode-script');
                if (existing) {
                    existing.addEventListener('load', resolve);
                    existing.addEventListener('error', reject);
                    return;
                }
                const s = document.createElement('script');
                s.id = 'html5-qrcode-script';
                s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                s.onload = resolve;
                s.onerror = reject;
                document.body.appendChild(s);
            });
        }

        window.qcFocusInput = function () {
            setTimeout(() => {
                const input = document.getElementById('qcPackingBarcodeInput');
                if (input) { input.focus(); input.select(); }
            }, 150);
        }

        window.qcResumeCamera = function () {
            qcScannerBusy = false;
            try {
                if (qcScannerInstance && qcScannerReady) qcScannerInstance.resume();
            } catch (e) {}
            qcSetStatus('Kamera standby. Arahkan ke barcode dus.');
            qcFocusInput();
        }

        window.qcStartCamera = async function () {
            const el = document.getElementById('qcCameraScanner');
            if (!el) return;

            try {
                await loadQcScript();

                if (qcScannerReady && qcScannerInstance) { qcResumeCamera(); return; }

                el.innerHTML = '';
                qcScannerInstance = new Html5Qrcode('qcCameraScanner');

                const config = {
                    fps: 12,
                    qrbox: function(w, h) {
                        const isMobile = window.innerWidth < 768;
                        return {
                            width: Math.floor(w * (isMobile ? 0.92 : 0.86)),
                            height: Math.floor(h * (isMobile ? 0.28 : 0.32))
                        };
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

                await qcScannerInstance.start(
                    { facingMode: 'environment' },
                    config,
                    async function(decodedText) {
                        const code = String(decodedText).trim();
                        const now = Date.now();
                        if (!code || qcScannerBusy) return;
                        if (qcScannerLastCode === code && (now - qcScannerLastTime) < 2500) return;

                        qcScannerBusy = true;
                        qcScannerLastCode = code;
                        qcScannerLastTime = now;
                        qcSetStatus('Terbaca: ' + code + '. Menyimpan...');

                        try {
                            if (qcScannerInstance && qcScannerReady) qcScannerInstance.pause(true);
                        } catch (e) {}

                        const input = document.getElementById('qcPackingBarcodeInput');
                        if (input) {
                            input.value = code;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        try {
                            await $wire.set('packingBarcode', code);
                            await $wire.scanDus();
                        } catch (e) {
                            qcSetStatus('Gagal mengirim ke server.', true);
                            setTimeout(() => qcResumeCamera(), 800);
                        }
                    },
                    function() {}
                );

                qcScannerReady = true;
                qcScannerBusy = false;
                qcSetStatus('Kamera standby. Arahkan ke barcode dus.');
                qcFocusInput();
            } catch (e) {
                qcScannerReady = false;
                qcScannerBusy = false;
                qcSetStatus('Kamera gagal. Pakai HTTPS/localhost dan izinkan kamera.', true);
                qcFocusInput();
            }
        }

        window.qcStopCamera = async function () {
            try {
                if (qcScannerInstance && qcScannerReady) {
                    await qcScannerInstance.stop();
                    await qcScannerInstance.clear();
                }
            } catch (e) {}

            qcScannerInstance = null;
            qcScannerReady = false;
            qcScannerBusy = false;
            qcScannerLastCode = '';
            qcScannerLastTime = 0;

            const el = document.getElementById('qcCameraScanner');
            if (el) el.innerHTML = '';

            qcSetStatus('Kamera dimatikan.');
        }

        Livewire.on('scan-modal-opened', () => {
            setTimeout(() => { qcFocusInput(); qcStartCamera(); }, 450);
        });

        Livewire.on('scan-ready-again', () => {
            setTimeout(() => qcResumeCamera(), 450);
        });

        Livewire.on('scan-modal-closed', () => {
            qcStopCamera();
        });

        window.qcConfirmSendToFgw = function (id) {
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
                if (result.isConfirmed) $wire.sendToFgw(id);
            });
        }

        window.qcConfirmForceComplete = function (id) {
            Swal.fire({
                title: 'Kunci troli?',
                text: 'Troli akan dikunci & tidak bisa diubah lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kunci',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-warning rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) $wire.forceCompleteTrolley(id);
            });
        }

        window.qcConfirmRemoveDus = function (trolleyId, packingUnitId) {
            Swal.fire({
                title: 'Keluarkan dus?',
                text: 'Status dus akan kembali menjadi PRINTED.',
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
                if (result.isConfirmed) $wire.removeDusFromTrolley(trolleyId, packingUnitId);
            });
        }
    </script>
    @endscript
</div>
