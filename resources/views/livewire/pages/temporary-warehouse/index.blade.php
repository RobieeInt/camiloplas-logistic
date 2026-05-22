<div>
    <style>
        .tw-camera-box { min-height: 320px; }
        #twCameraScanner { width: 100%; min-height: 320px; }
        #twCameraScanner video { border-radius: 1rem; object-fit: cover; }

        @media (max-width: 767.98px) {
            .tw-camera-box { min-height: 260px; }
            #twCameraScanner { min-height: 260px; }
            .tw-scan-input { font-size: 1.35rem !important; height: 58px; }
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Temporary Warehouse</h4>
            <div class="text-muted">Print barcode dus &amp; scan produk masuk TW sebelum diteruskan ke QC.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success rounded-3 px-4" wire:click="openScanModal">
                <i class="bi bi-upc-scan me-1"></i>
                Scan Produk
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
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Printed Hari Ini</div>
                    <div class="fs-3 fw-bold">{{ $summary->total_printed_today }}</div>
                    <div class="small text-muted">Dus barcode</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Belum Scan TW</div>
                    <div class="fs-3 fw-bold text-warning">{{ $summary->total_ready_scan }}</div>
                    <div class="small text-muted">Status PRINTED</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Sudah Scan TW</div>
                    <div class="fs-3 fw-bold text-success">{{ $summary->total_tw_scanned }}</div>
                    <div class="small text-muted">Siap ke QC troli</div>
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
                            <th>Print By</th>
                            <th>TW Scan By</th>
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
                                        <span class="badge rounded-pill text-bg-warning text-dark">PRINTED</span>
                                    @elseif ($unit->status === 'TW_SCANNED')
                                        <span class="badge rounded-pill text-bg-success">TW SCANNED</span>
                                    @elseif ($unit->status === 'SCANNED_TO_TROLLEY')
                                        <span class="badge rounded-pill text-bg-primary">IN TROLLEY</span>
                                    @elseif ($unit->status === 'SENT_FGW')
                                        <span class="badge rounded-pill text-bg-info text-dark">SENT FGW</span>
                                    @elseif ($unit->status === 'RECEIVED_FGW')
                                        <span class="badge rounded-pill text-bg-secondary">FGW</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">{{ $unit->status }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="small">{{ $unit->printed_by_name ?? '-' }}</div>
                                    <small class="text-muted">
                                        {{ $unit->printed_at ? \Carbon\Carbon::parse($unit->printed_at)->format('d/m/Y H:i') : '-' }}
                                    </small>
                                </td>

                                <td>
                                    @if ($unit->prod_scanned_by_name)
                                        <div class="small fw-semibold text-success">{{ $unit->prod_scanned_by_name }}</div>
                                        <small class="text-muted">
                                            {{ $unit->prod_scanned_at ? \Carbon\Carbon::parse($unit->prod_scanned_at)->format('d/m/Y H:i') : '-' }}
                                        </small>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
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
                                <td colspan="8" class="text-center text-muted py-5">
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

    {{-- ── Modal Print Barcode ──────────────────────────────────────── --}}
    <div
        class="modal fade @if($showPrintModal) show d-block @endif"
        tabindex="-1"
        style="@if($showPrintModal) background: rgba(15,23,42,.55); @else display:none; @endif"
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
                                        @if ($po->so_number) [{{ $po->so_number }}] @endif
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
                                <input type="number" wire:model.live="totalBox"
                                    class="form-control rounded-3 @error('totalBox') is-invalid @enderror"
                                    min="1" max="500" placeholder="contoh: 10">
                                @error('totalBox') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label fw-semibold">
                                    Qty per Dus <span class="text-muted fw-normal small">(PCS)</span>
                                </label>
                                <input type="number" wire:model.live="qtyPerBox"
                                    class="form-control rounded-3 @error('qtyPerBox') is-invalid @enderror"
                                    min="1" max="100000" placeholder="contoh: 1000">
                                @error('qtyPerBox') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        @if ($totalBox > 0 && $qtyPerBox > 0)
                            <div class="rounded-3 bg-primary-subtle border border-primary-subtle p-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Jumlah barcode</span>
                                    <span class="fw-semibold">{{ number_format($totalBox) }} dus</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Isi per dus</span>
                                    <span class="fw-semibold">{{ number_format($qtyPerBox) }} PCS</span>
                                </div>
                                {{-- <div class="border-top border-primary-subtle mt-2 pt-2 d-flex justify-content-between">
                                    <span class="fw-semibold">Total PCS</span>
                                    <span class="fw-bold text-primary">{{ number_format($totalBox * $qtyPerBox) }} PCS</span>
                                </div> --}}
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" wire:click="closePrintModal" class="btn btn-light border rounded-3 px-4">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-printer me-1"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modal Scan Produk TW ─────────────────────────────────────── --}}
    <div
        class="modal fade @if($showScanModal) show d-block @endif"
        tabindex="-1"
        style="@if($showScanModal) background: rgba(15,23,42,.55); @else display:none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Scan Produk — Temporary Warehouse</h5>
                        <div class="text-muted small">Scan barcode dus yang baru datang dari produksi. Wajib sebelum bisa masuk ke QC troli.</div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeScanModal"></button>
                </div>

                <form wire:submit.prevent="scanProd">
                    <div class="modal-body">
                        @if (session('tw_scan_success'))
                            <div class="alert alert-success border-0 rounded-3">{{ session('tw_scan_success') }}</div>
                        @endif
                        @if (session('tw_scan_error'))
                            <div class="alert alert-danger border-0 rounded-3">{{ session('tw_scan_error') }}</div>
                        @endif

                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div wire:ignore class="border rounded-4 overflow-hidden bg-dark tw-camera-box">
                                    <div id="twCameraScanner"></div>
                                </div>

                                <div id="twCameraScannerStatus" class="small text-muted mt-2">
                                    Menunggu kamera...
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="twStartCamera()">
                                        <i class="bi bi-camera-video me-1"></i> Nyalakan Kamera
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border rounded-3" onclick="twStopCamera()">
                                        <i class="bi bi-camera-video-off me-1"></i> Matikan
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <label class="form-label fw-semibold">Barcode Dus</label>
                                <input
                                    type="text"
                                    id="twScanBarcodeInput"
                                    wire:model.defer="twScanBarcode"
                                    class="form-control form-control-lg rounded-3 text-center fw-bold tw-scan-input"
                                    placeholder="Scan barcode"
                                    autocomplete="off"
                                >
                                @error('twScanBarcode')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror

                                <div class="form-text mb-3">Kamera HP atau scanner gun.</div>

                                <button type="submit" class="btn btn-success rounded-3 px-4 w-100">
                                    <i class="bi bi-upc-scan me-1"></i>
                                    Scan Masuk TW
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
        let twScannerInstance = null;
        let twScannerReady = false;
        let twScannerBusy = false;
        let twScannerLastCode = '';
        let twScannerLastTime = 0;

        function twSetStatus(msg, isError = false) {
            const el = document.getElementById('twCameraScannerStatus');
            if (!el) return;
            el.innerText = msg;
            el.classList.toggle('text-danger', isError);
            el.classList.toggle('text-muted', !isError);
        }

        function loadTwScript() {
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

        window.twFocusInput = function () {
            setTimeout(() => {
                const input = document.getElementById('twScanBarcodeInput');
                if (input) { input.focus(); input.select(); }
            }, 150);
        }

        window.twResumeCamera = function () {
            twScannerBusy = false;
            try {
                if (twScannerInstance && twScannerReady) twScannerInstance.resume();
            } catch (e) {}
            twSetStatus('Kamera standby. Arahkan ke barcode.');
            twFocusInput();
        }

        window.twStartCamera = async function () {
            const el = document.getElementById('twCameraScanner');
            if (!el) return;

            try {
                await loadTwScript();

                if (twScannerReady && twScannerInstance) { twResumeCamera(); return; }

                el.innerHTML = '';
                twScannerInstance = new Html5Qrcode('twCameraScanner');

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

                await twScannerInstance.start(
                    { facingMode: 'environment' },
                    config,
                    async function(decodedText) {
                        const code = String(decodedText).trim();
                        const now = Date.now();
                        if (!code || twScannerBusy) return;
                        if (twScannerLastCode === code && (now - twScannerLastTime) < 2500) return;

                        twScannerBusy = true;
                        twScannerLastCode = code;
                        twScannerLastTime = now;
                        twSetStatus('Terbaca: ' + code + '. Menyimpan...');

                        try {
                            if (twScannerInstance && twScannerReady) twScannerInstance.pause(true);
                        } catch (e) {}

                        const input = document.getElementById('twScanBarcodeInput');
                        if (input) {
                            input.value = code;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        try {
                            await $wire.set('twScanBarcode', code);
                            await $wire.scanProd();
                        } catch (e) {
                            twSetStatus('Gagal kirim ke server.', true);
                            setTimeout(() => twResumeCamera(), 800);
                        }
                    },
                    function() {}
                );

                twScannerReady = true;
                twScannerBusy = false;
                twSetStatus('Kamera standby. Arahkan ke barcode.');
                twFocusInput();
            } catch (e) {
                twScannerReady = false;
                twScannerBusy = false;
                twSetStatus('Kamera gagal. Pakai HTTPS/localhost dan izinkan kamera.', true);
                twFocusInput();
            }
        }

        window.twStopCamera = async function () {
            try {
                if (twScannerInstance && twScannerReady) {
                    await twScannerInstance.stop();
                    await twScannerInstance.clear();
                }
            } catch (e) {}

            twScannerInstance = null;
            twScannerReady = false;
            twScannerBusy = false;
            twScannerLastCode = '';
            twScannerLastTime = 0;

            const el = document.getElementById('twCameraScanner');
            if (el) el.innerHTML = '';

            twSetStatus('Kamera dimatikan.');
        }

        Livewire.on('tw-scan-modal-opened', () => {
            setTimeout(() => { twFocusInput(); twStartCamera(); }, 450);
        });

        Livewire.on('tw-scan-ready-again', () => {
            setTimeout(() => twResumeCamera(), 450);
        });

        Livewire.on('tw-scan-modal-closed', () => {
            twStopCamera();
        });
    </script>
    @endscript
</div>
