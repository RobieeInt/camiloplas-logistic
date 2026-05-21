<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Finish Goods Warehouse</h4>
            <div class="text-muted">
                Validasi troli dari Temporary Warehouse.
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

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Menunggu Validasi</div>
                    <div class="fs-2 fw-bold">{{ $summary->waiting_validation }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted small">Sudah Diterima FGW</div>
                    <div class="fs-2 fw-bold">{{ $summary->received_today }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold mb-1">Scan Troli</h5>
            <div class="text-muted small">
                Scan barcode / QR troli dari TWH.
            </div>
        </div>

        <div class="card-body px-4 pb-4">
            <form wire:submit.prevent="scanTrolley">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-10">
                        <label class="form-label fw-semibold">Barcode Troli</label>

                        <input
                            type="text"
                            wire:model.defer="trolleyBarcode"
                            class="form-control form-control-lg rounded-3"
                            placeholder="Scan barcode troli"
                            autofocus
                        >
                    </div>

                    <div class="col-lg-2 d-grid">
                        <button class="btn btn-primary btn-lg rounded-3">
                            Scan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        class="modal fade @if($showReceiveModal) show d-block @endif"
        tabindex="-1"
        style="@if($showReceiveModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Validasi Dus FGW</h5>
                        <div class="text-muted small">
                            Semua dus wajib discan ulang.
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
                            <div class="fw-bold fs-5">
                                {{ $selectedTrolley->trolley_code ?? '-' }}
                            </div>

                            <code>{{ $selectedTrolley->barcode ?? '-' }}</code>

                            <div class="mt-3">
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

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Box</th>
                                        <th>Item</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($trolleyItems as $item)
                                        <tr>
                                            <td><code>{{ $item->barcode }}</code></td>
                                            <td>{{ $item->box_number }}</td>
                                            <td>{{ $item->item_name }}</td>
                                            <td>
                                                @if (in_array($item->barcode, $validatedItems))
                                                    <span class="badge text-bg-success rounded-pill">
                                                        VALIDATED
                                                    </span>
                                                @else
                                                    <span class="badge text-bg-light border rounded-pill">
                                                        WAITING
                                                    </span>
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
</div>
