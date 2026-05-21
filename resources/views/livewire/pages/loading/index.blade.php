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
                Pilih SO / DO, scan dus ke truck, validasi kelengkapan, lalu complete loading.
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
                                {{ $order->total_loaded }}/{{ $order->total_required }} dus
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
                            <div class="border rounded-4 p-3 mb-3 bg-light">
                                <div class="row g-3 align-items-center">
                                    <div class="col-lg-3">
                                        <div class="small text-muted">Delivery Order</div>
                                        <div class="fw-bold fs-5">{{ $selectedOrder->do_number }}</div>
                                        <div class="small">{{ $selectedOrder->so_number }}</div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="small text-muted">Customer</div>
                                        <div class="fw-semibold">{{ $selectedOrder->customer_name }}</div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="small text-muted">Truck</div>
                                        <div class="fw-semibold">{{ $selectedOrder->truck_number ?: '-' }}</div>
                                    </div>

                                    <div class="col-lg-3">
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
                        @endif

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
                                    <div class="card-header bg-white">
                                        <div class="fw-bold">Target DO</div>
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
                                                    @php
                                                        $percent = $item->required_boxes > 0
                                                            ? ($item->loaded_boxes / $item->required_boxes) * 100
                                                            : 0;
                                                    @endphp

                                                    <tr>
                                                        <td class="ps-3">
                                                            <div class="fw-semibold">{{ $item->item_name }}</div>
                                                            <small class="text-muted">{{ $item->item_code }}</small>
                                                        </td>

                                                        <td style="min-width: 140px;">
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <span>{{ $item->loaded_boxes }}/{{ $item->required_boxes }}</span>
                                                                <span>{{ number_format($percent, 0) }}%</span>
                                                            </div>

                                                            <div class="progress" style="height: 8px;">
                                                                <div
                                                                    class="progress-bar"
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
                text: 'Sistem akan validasi kelengkapan DO sebelum status menjadi LOADED.',
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
