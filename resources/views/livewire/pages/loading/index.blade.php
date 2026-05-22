<div>
    <style>
        @media (max-width: 767.98px) {
            .loading-summary .fs-2 {
                font-size: 1.5rem !important;
            }

            .loading-table th,
            .loading-table td {
                font-size: .85rem;
                white-space: nowrap;
            }

            .loading-action-stack {
                display: grid !important;
                grid-template-columns: 1fr;
                gap: .5rem;
            }

            .loading-action-stack .btn {
                width: 100%;
            }
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Ready To Deliver</h4>
            <div class="text-muted">
                Pilih SO / DO, tentukan truck, scan dus FGW ke truck, lalu complete loading.
            </div>
        </div>

        <button
            type="button"
            class="btn btn-primary rounded-3 px-4"
            wire:click="openCreateDoModal"
        >
            <i class="bi bi-plus-circle me-1"></i>
            Buat DO
        </button>
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

    @if (session('create_do_error'))
        <div class="alert alert-danger rounded-3 border-0 shadow-sm">
            {{ session('create_do_error') }}
        </div>
    @endif

    <div class="row g-3 mb-4 loading-summary">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Ready Order</div>
                    <div class="fs-2 fw-bold">{{ $summary->ready_orders }}</div>
                    <div class="small text-muted">Belum loading</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Sedang Loading</div>
                    <div class="fs-2 fw-bold">{{ $summary->loading_orders }}</div>
                    <div class="small text-muted">Proses truck</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Loaded</div>
                    <div class="fs-2 fw-bold">{{ $summary->loaded_orders }}</div>
                    <div class="small text-muted">Siap dokumen</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Stock FGW</div>
                    <div class="fs-2 fw-bold">{{ $summary->stock_fgw }}</div>
                    <div class="small text-muted">Dus available</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Pilih SO / DO</h5>
            <div class="text-muted small">
                Loading hanya bisa dilakukan untuk order READY atau LOADING.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 loading-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">DO</th>
                        <th>SO</th>
                        <th>Customer</th>
                        <th>Truck</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($readyOrders as $order)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $order->do_number }}</div>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}
                                </small>
                            </td>

                            <td>{{ $order->so_number }}</td>

                            <td>{{ $order->customer_name }}</td>

                            <td>
                                <span class="badge rounded-pill text-bg-light border">
                                    {{ $order->truck_number ?: '-' }}
                                </span>
                            </td>

                            <td>
                                @if ($order->status === 'READY')
                                    <span class="badge rounded-pill text-bg-primary">READY</span>
                                @else
                                    <span class="badge rounded-pill text-bg-warning">LOADING</span>
                                @endif
                            </td>

                            <td class="text-end pe-4">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary rounded-3"
                                    wire:click="openLoadingModal({{ $order->id }})"
                                >
                                    <i class="bi bi-truck me-1"></i>
                                    Loading
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada SO / DO ready loading.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Riwayat Loaded</h5>
            <div class="text-muted small">
                Order yang sudah selesai loading dan siap cetak dokumen.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 loading-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">DO</th>
                        <th>SO</th>
                        <th>Customer</th>
                        <th>Truck</th>
                        <th>Progress</th>
                        <th>Loaded</th>
                        <th>User</th>
                        <th class="text-end pe-4">Dokumen</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($recentLoadedOrders as $order)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $order->do_number }}</div>
                                <span class="badge rounded-pill text-bg-success">{{ $order->status }}</span>
                            </td>

                            <td>{{ $order->so_number }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->truck_number ?: '-' }}</td>

                            <td>
                                @if ($order->total_required > 0)
                                    {{ $order->total_loaded }}/{{ $order->total_required }} dus
                                @else
                                    {{ $order->total_loaded }} dus
                                    <small class="text-muted d-block">tanpa target</small>
                                @endif
                            </td>

                            <td>
                                {{ $order->loaded_at ? \Carbon\Carbon::parse($order->loaded_at)->format('d M Y H:i') : '-' }}
                            </td>

                            <td>{{ $order->loaded_by_name ?? '-' }}</td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <a
                                        href="{{ route('loading.print-do', $order->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-light border rounded-3"
                                    >
                                        <i class="bi bi-printer me-1"></i>
                                        DO
                                    </a>

                                    <a
                                        href="{{ route('loading.print-surat-jalan', $order->id) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-light border rounded-3"
                                    >
                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        Surat Jalan
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Belum ada order loaded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Buat DO --}}
    <div
        class="modal fade @if($showCreateDoModal) show d-block @endif"
        tabindex="-1"
        style="@if($showCreateDoModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-plus-circle me-2 text-primary"></i>
                            Buat Delivery Order
                        </h5>
                        <div class="text-muted small">Gabungkan 1 atau lebih SO menjadi satu DO.</div>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeCreateDoModal"></button>
                </div>

                <form wire:submit.prevent="submitCreateDo">
                    <div class="modal-body">

                        @if (session('create_do_error'))
                            <div class="alert alert-danger rounded-3 border-0">
                                {{ session('create_do_error') }}
                            </div>
                        @endif

                        {{-- Step 1: Pilih SO --}}
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="fw-semibold small mb-2 text-primary">
                                <i class="bi bi-1-circle-fill me-1"></i>
                                Pilih SO
                            </div>

                            <div class="d-flex gap-2 mb-2">
                                <select
                                    wire:model.live="createDoAddSoId"
                                    class="form-select rounded-3 flex-grow-1"
                                >
                                    <option value="">— Pilih SO untuk ditambahkan —</option>
                                    @foreach ($salesOrders as $so)
                                        @if (!in_array($so->id, $createDoSoIds))
                                            <option value="{{ $so->id }}">
                                                {{ $so->so_number }} — {{ $so->customer_name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <button
                                    type="button"
                                    class="btn btn-primary rounded-3 px-3"
                                    wire:click="addSoToDo"
                                    @disabled(!$createDoAddSoId)
                                >
                                    <i class="bi bi-plus-lg me-1"></i>
                                    Tambah
                                </button>
                            </div>

                            {{-- Chips SO terpilih --}}
                            @if (count($createDoSoIds) > 0)
                                <div class="d-flex flex-wrap gap-2 pt-1">
                                    @foreach ($createDoSos as $so)
                                        <span class="badge rounded-pill text-bg-primary d-inline-flex align-items-center gap-2 px-3 py-2" style="font-size: .82rem; font-weight: 500;">
                                            <i class="bi bi-file-earmark-text"></i>
                                            {{ $so->so_number }}
                                            <span class="fw-normal opacity-75">{{ $so->customer_name }}</span>
                                            <button
                                                type="button"
                                                class="btn-close btn-close-white ms-1"
                                                style="font-size: .55rem;"
                                                wire:click="removeSoFromDo({{ $so->id }})"
                                            ></button>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted small text-center py-2 bg-light rounded-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Belum ada SO dipilih
                                </div>
                            @endif
                        </div>

                        {{-- Step 2: DO Number --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <span class="text-primary me-1"><i class="bi bi-2-circle-fill"></i></span>
                                DO Number
                            </label>
                            <input
                                type="text"
                                wire:model="createDoNumber"
                                class="form-control form-control-lg rounded-3 @error('createDoNumber') is-invalid @enderror"
                                placeholder="DO-YYYYMMDD-0001"
                            >
                            @error('createDoNumber')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Auto-generated, bisa diubah manual.</div>
                        </div>

                        {{-- Step 3: Item dari SO terpilih --}}
                        @if (count($createDoSoIds) > 0 && count($createDoDetails) > 0)
                            <div class="border rounded-4 overflow-hidden">
                                <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold small">
                                        <span class="text-primary me-1"><i class="bi bi-3-circle-fill"></i></span>
                                        Item dari SO terpilih
                                    </span>
                                    <span class="badge rounded-pill text-bg-light border text-muted">0 = tanpa target</span>
                                </div>

                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Item</th>
                                            <th class="text-end">Sisa Order SO</th>
                                            <th class="text-end">Stok FGW</th>
                                            <th style="width: 160px;">
                                                Target Box
                                                <span class="fw-normal text-muted">(opsional)</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($createDoDetails as $detail)
                                            @php
                                                $stockDus      = (int) $detail->stock_dus;
                                                $stockPcs      = (int) $detail->stock_pcs;
                                                $qtyPerBox     = $detail->qty_per_box ? (int) $detail->qty_per_box : null;
                                                $remainingPcs  = (int) $detail->remaining_pcs;
                                                $alreadyLoaded = (int) $detail->already_loaded_pcs;
                                                $totalQty      = (int) $detail->qty;
                                                $stockEnough   = $stockPcs >= $remainingPcs;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $detail->item_name }}</div>
                                                    <small class="text-muted">{{ $detail->item_code }}</small>
                                                </td>
                                                <td class="text-end">
                                                    <div class="fw-semibold {{ $remainingPcs <= 0 ? 'text-success' : '' }}">
                                                        {{ number_format($remainingPcs) }}
                                                        <small class="fw-normal text-muted">{{ $detail->uom }}</small>
                                                    </div>
                                                    @if ($alreadyLoaded > 0)
                                                        <small class="text-muted">
                                                            Total {{ number_format($totalQty) }} · Terkirim {{ number_format($alreadyLoaded) }}
                                                        </small>
                                                    @else
                                                        <small class="text-muted">Total order</small>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if ($stockDus > 0)
                                                        <div class="fw-semibold {{ $stockEnough ? 'text-success' : 'text-warning' }}">
                                                            {{ $stockDus }} dus
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ number_format($stockPcs) }} PCS
                                                            @if ($qtyPerBox)
                                                                · {{ number_format($qtyPerBox) }}/dus
                                                            @endif
                                                        </small>
                                                    @else
                                                        <div class="text-danger fw-semibold">0 dus</div>
                                                        <small class="text-muted">Belum ada stok</small>
                                                    @endif
                                                </td>
                                                <td class="pe-4">
                                                    <input
                                                        type="number"
                                                        wire:model="createDoBoxes.{{ $detail->item_id }}"
                                                        class="form-control rounded-3 text-center fw-semibold"
                                                        min="0"
                                                        placeholder="0"
                                                        @if ($stockDus > 0) max="{{ $stockDus }}" @endif
                                                    >
                                                    <div class="form-text text-center">
                                                        @if ($stockDus > 0)
                                                            maks {{ $stockDus }} dus
                                                        @else
                                                            0 = tanpa target
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif (count($createDoSoIds) > 0 && count($createDoDetails) === 0)
                            <div class="text-center text-muted py-4 border rounded-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                SO yang dipilih belum memiliki detail item.
                            </div>
                        @else
                            <div class="text-center text-muted py-4 border rounded-4 bg-light">
                                <i class="bi bi-arrow-up-circle fs-2 d-block mb-2 opacity-25"></i>
                                Tambahkan SO untuk melihat item yang akan di-DO-kan.
                            </div>
                        @endif

                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" wire:click="closeCreateDoModal" class="btn btn-light border rounded-3 px-4">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary rounded-3 px-4"
                            @disabled(count($createDoSoIds) === 0 || count($createDoDetails) === 0)
                        >
                            <i class="bi bi-check-circle me-1"></i>
                            Generate DO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Loading --}}
    <div
        class="modal fade @if($showLoadingModal) show d-block @endif"
        tabindex="-1"
        style="@if($showLoadingModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Loading ke Truck</h5>
                        <div class="text-muted small">
                            Scan barcode dus saat barang masuk ke truck / container.
                        </div>
                    </div>

                    <button type="button" class="btn-close" wire:click="closeLoadingModal"></button>
                </div>

                <form wire:submit.prevent="scanDus">
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

                        @if ($selectedOrder)
                            {{-- Info DO --}}
                            <div class="border rounded-4 p-3 mb-3 bg-light">
                                <div class="row g-3 align-items-center">
                                    <div class="col-lg-4">
                                        <div class="small text-muted">Delivery Order</div>
                                        <div class="fw-bold fs-5">{{ $selectedOrder->do_number }}</div>
                                        <div class="small text-muted">{{ $selectedOrder->so_number }}</div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="small text-muted">Customer</div>
                                        <div class="fw-semibold">{{ $selectedOrder->customer_name }}</div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="small text-muted">Status</div>
                                        @if ($selectedOrder->status === 'READY')
                                            <span class="badge rounded-pill text-bg-primary">READY</span>
                                        @elseif ($selectedOrder->status === 'LOADING')
                                            <span class="badge rounded-pill text-bg-warning">LOADING</span>
                                        @else
                                            <span class="badge rounded-pill text-bg-success">{{ $selectedOrder->status }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Truck Selector --}}
                            <div class="border rounded-4 p-3 mb-3 @error('truckNumber') border-danger @enderror">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold small">
                                        <i class="bi bi-truck me-1 text-primary"></i>
                                        Truck / Kendaraan
                                    </span>
                                    <span class="badge text-bg-danger rounded-pill" style="font-size: .7rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i>Wajib diisi
                                    </span>
                                </div>

                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5">
                                        <label class="form-label small text-muted mb-1">Pilih dari master kendaraan</label>
                                        <select
                                            class="form-select rounded-3"
                                            wire:change="selectVehicle($event.target.value)"
                                        >
                                            <option value="">— Pilih kendaraan —</option>
                                            @foreach ($vehicles as $v)
                                                <option
                                                    value="{{ $v->id }}"
                                                    {{ $selectedVehicleId == $v->id ? 'selected' : '' }}
                                                >
                                                    {{ $v->vehicle_number }}
                                                    @if ($v->vehicle_type) ({{ $v->vehicle_type }}) @endif
                                                    @if ($v->driver_name) — {{ $v->driver_name }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-1 d-none d-lg-flex align-items-center justify-content-center pb-1">
                                        <div class="vr" style="height: 36px;"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label small text-muted mb-1">
                                            No. Polisi
                                            <span class="fw-normal">(atau ketik manual)</span>
                                        </label>
                                        <input
                                            type="text"
                                            wire:model="truckNumber"
                                            class="form-control rounded-3 text-uppercase fw-semibold @error('truckNumber') is-invalid @enderror"
                                            placeholder="cth: B 9123 XYZ"
                                            autocomplete="off"
                                        >
                                        @error('truckNumber')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small text-muted mb-1">
                                        Nama Driver
                                        <span class="badge text-bg-danger rounded-pill ms-1" style="font-size:.7rem;">Wajib</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="driverName"
                                        class="form-control rounded-3 @error('driverName') is-invalid @enderror"
                                        placeholder="cth: Budi Santoso"
                                        autocomplete="off"
                                    >
                                    @error('driverName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Scan Dus --}}
                        <div class="row g-3 mb-3">
                            <div class="col-lg-8">
                                <label class="form-label fw-semibold">Barcode Dus</label>

                                <input
                                    type="text"
                                    id="loadingPackingInput"
                                    wire:model.defer="packingBarcode"
                                    class="form-control form-control-lg rounded-3 text-center fw-bold"
                                    placeholder="Scan barcode dus"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="col-lg-4 d-grid align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg rounded-3">
                                    <i class="bi bi-upc-scan me-1"></i>
                                    Scan Loading
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-5">
                                <div class="card border rounded-4">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <div class="fw-bold">Target DO</div>
                                        @php
                                            $totalLoaded   = collect($orderItems)->sum('loaded_boxes');
                                            $totalRequired = collect($orderItems)->sum('required_boxes');
                                        @endphp
                                        <div class="text-end">
                                            <span class="fs-5 fw-bold text-primary">{{ $totalLoaded }}</span>
                                            @if ($totalRequired > 0)
                                                <span class="text-muted small">/{{ $totalRequired }}</span>
                                            @endif
                                            <div class="text-muted" style="font-size: .7rem; line-height: 1;">total dus</div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Item</th>
                                                    <th>Progress</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @forelse ($orderItems as $item)
                                                    <tr>
                                                        <td class="ps-3">
                                                            <div class="fw-semibold">{{ $item->item_name }}</div>
                                                            <small class="text-muted">{{ $item->item_code }}</small>
                                                        </td>

                                                        @if ($item->required_boxes > 0)
                                                            @php
                                                                $percent = min(($item->loaded_boxes / $item->required_boxes) * 100, 100);
                                                            @endphp
                                                            <td style="min-width: 150px;">
                                                                <div class="d-flex justify-content-between small mb-1">
                                                                    <span class="fw-semibold">{{ $item->loaded_boxes }}<span class="fw-normal text-muted">/{{ $item->required_boxes }} dus</span></span>
                                                                    <span class="text-muted">{{ number_format($percent, 0) }}%</span>
                                                                </div>
                                                                <div class="progress" style="height: 8px;">
                                                                    <div
                                                                        class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}"
                                                                        style="width: {{ $percent }}%"
                                                                    ></div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if ($item->loaded_boxes >= $item->required_boxes)
                                                                    <span class="badge rounded-pill text-bg-success">OK</span>
                                                                @else
                                                                    <span class="badge rounded-pill text-bg-light border">WAITING</span>
                                                                @endif
                                                            </td>
                                                        @else
                                                            <td style="min-width: 150px;">
                                                                <div class="fw-bold fs-5 lh-1 text-primary">{{ $item->loaded_boxes }}</div>
                                                                <small class="text-muted">dus discan</small>
                                                            </td>
                                                            <td>
                                                                @if ($item->loaded_boxes > 0)
                                                                    <span class="badge rounded-pill text-bg-info text-white">SCANNING</span>
                                                                @else
                                                                    <span class="badge rounded-pill text-bg-light border">BEBAS</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            Item DO kosong.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="card border rounded-4">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">Dus Sudah Masuk Truck</div>
                                            <small class="text-muted">Data hasil scan loading.</small>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-success rounded-3"
                                            onclick="confirmCompleteLoading()"
                                        >
                                            <i class="bi bi-check-circle me-1"></i>
                                            Complete Loading
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3">Barcode</th>
                                                    <th>Item</th>
                                                    <th>Troli</th>
                                                    <th>Rak</th>
                                                    <th>Loaded</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @forelse ($loadedItems as $item)
                                                    <tr>
                                                        <td class="ps-3">
                                                            <code>{{ $item->barcode }}</code>
                                                            <div class="small text-muted">{{ $item->box_number }}</div>
                                                        </td>

                                                        <td>
                                                            <div class="fw-semibold">{{ $item->item_name }}</div>
                                                            <small class="text-muted">{{ $item->item_code }}</small>
                                                        </td>

                                                        <td>{{ $item->trolley_code ?? '-' }}</td>
                                                        <td>{{ $item->rack_code ?? '-' }}</td>

                                                        <td>
                                                            <div>{{ $item->loaded_by_name ?? '-' }}</div>
                                                            <small class="text-muted">
                                                                {{ $item->loaded_at ? \Carbon\Carbon::parse($item->loaded_at)->format('H:i') : '-' }}
                                                            </small>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">
                                                            Belum ada dus discan ke truck.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    @script
    <script>
        function focusLoadingInput() {
            setTimeout(() => {
                const input = document.getElementById('loadingPackingInput');

                if (input) {
                    input.value = '';
                    input.focus();
                    input.select();
                }
            }, 150);
        }

        Livewire.on('loading-modal-opened', () => {
            focusLoadingInput();
        });

        Livewire.on('loading-ready-again', () => {
            focusLoadingInput();
        });

        Livewire.on('loading-modal-closed', () => {
            focusLoadingInput();
        });

        window.confirmCompleteLoading = function () {
            Swal.fire({
                title: 'Complete loading?',
                text: 'Status DO akan berubah menjadi LOADED dan truck number disimpan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, complete',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-success rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.completeLoading();
                }
            });
        }
    </script>
    @endscript
</div>
