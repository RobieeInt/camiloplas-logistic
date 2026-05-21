<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Menus</h4>
            <div class="text-muted">Kelola sidebar, route, permission, dan urutan menu.</div>
        </div>

        @can('menus.create')
            <button
                type="button"
                class="btn btn-primary rounded-3 px-4"
                wire:click="openCreateModal"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Tambah Menu
            </button>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            {{ session('error') }}
        </div>
    @endif

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
                                placeholder="Cari menu, route, atau permission..."
                            >
                        </div>
                    </div>

                    <div class="col-md-6 text-md-end text-muted small">
                        Total data: {{ $menus->total() }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Menu</th>
                            <th>Parent</th>
                            <th>Route</th>
                            <th>Permission</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th class="text-end pe-4" width="160">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($menus as $menu)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                            style="width: 42px; height: 42px;"
                                        >
                                            <i class="{{ $menu->icon ?: 'bi bi-list' }}"></i>
                                        </div>

                                        <div>
                                            <div class="fw-semibold">{{ $menu->name }}</div>
                                            <small class="text-muted">ID: {{ $menu->id }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if ($menu->parent_name)
                                        <span class="badge rounded-pill text-bg-light border">
                                            {{ $menu->parent_name }}
                                        </span>
                                    @else
                                        <span class="badge rounded-pill text-bg-primary">
                                            Parent
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($menu->route)
                                        <code>{{ $menu->route }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($menu->permission_name)
                                        <span class="badge rounded-pill text-bg-light border">
                                            {{ $menu->permission_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">Public</span>
                                    @endif
                                </td>

                                <td>{{ $menu->sort_order }}</td>

                                <td>
                                    @if ($menu->is_active)
                                        <span class="badge rounded-pill text-bg-success">Active</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-end pe-4">
                                    @can('menus.edit')
                                        <button
                                            type="button"
                                            wire:click="openEditModal({{ $menu->id }})"
                                            class="btn btn-sm btn-light border rounded-3"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    @endcan

                                    @can('menus.delete')
                                        <button
                                            type="button"
                                            onclick="confirmDeleteMenu({{ $menu->id }})"
                                            class="btn btn-sm btn-light border text-danger rounded-3"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data menu.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-white">
                {{ $menus->links() }}
            </div>
        </div>
    </div>

    <div
        class="modal fade @if($showModal) show d-block @endif"
        tabindex="-1"
        style="@if($showModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">
                            {{ $isEdit ? 'Edit Menu' : 'Tambah Menu' }}
                        </h5>
                        <div class="text-muted small">
                            Atur menu sidebar dan permission akses.
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        wire:click="closeModal"
                    ></button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Parent Menu</label>
                                <select wire:model="parentId" class="form-select rounded-3">
                                    <option value="">Tidak ada / Parent</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}">
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parentId')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Menu</label>
                                <input
                                    type="text"
                                    wire:model="name"
                                    class="form-control rounded-3"
                                    placeholder="Contoh: Temporary Warehouse"
                                >
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Route Name</label>
                                <input
                                    type="text"
                                    wire:model="route"
                                    class="form-control rounded-3"
                                    placeholder="Contoh: users.index"
                                >
                                @error('route')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Icon Bootstrap</label>
                                <input
                                    type="text"
                                    wire:model="icon"
                                    class="form-control rounded-3"
                                    placeholder="Contoh: bi bi-people"
                                >
                                @error('icon')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Permission</label>
                                <select wire:model="permissionName" class="form-select rounded-3">
                                    <option value="">Public / Tanpa Permission</option>
                                    @foreach ($permissions as $permission)
                                        <option value="{{ $permission->name }}">
                                            {{ $permission->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('permissionName')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sort Order</label>
                                <input
                                    type="number"
                                    wire:model="sortOrder"
                                    class="form-control rounded-3"
                                    min="0"
                                >
                                @error('sortOrder')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label
                                    class="border rounded-3 p-3 w-100 d-flex align-items-center justify-content-between"
                                    style="cursor: pointer;"
                                >
                                    <div>
                                        <div class="fw-semibold">Status Menu</div>
                                        <small class="text-muted">Menu aktif akan muncul di sidebar sesuai permission.</small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            wire:model="isActive"
                                        >
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="btn btn-light border rounded-3 px-4"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary rounded-3 px-4"
                        >
                            <i class="bi bi-save me-1"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @script
    <script>
        window.confirmDeleteMenu = function (id) {
            Swal.fire({
                title: 'Hapus menu?',
                text: 'Child menu akan dilepas dari parent menu ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger rounded-3 px-4 ms-2',
                    cancelButton: 'btn btn-light border rounded-3 px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.delete(id);
                }
            });
        }
    </script>
    @endscript
</div>
