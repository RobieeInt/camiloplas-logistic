<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Roles</h4>
            <div class="text-muted">Kelola role dan permission akses sistem.</div>
        </div>

        <button
            type="button"
            class="btn btn-primary rounded-3 px-4"
            wire:click="openCreateModal"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Tambah Role
        </button>
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
                                placeholder="Cari role..."
                            >
                        </div>
                    </div>

                    <div class="col-md-6 text-md-end text-muted small">
                        Total data: {{ $roles->total() }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Role</th>
                            <th>Guard</th>
                            <th>Permission</th>
                            <th>Dibuat</th>
                            <th class="text-end pe-4" width="180">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center"
                                            style="width: 42px; height: 42px;"
                                        >
                                            <i class="bi bi-shield-lock"></i>
                                        </div>

                                        <div>
                                            <div class="fw-semibold">{{ $role->name }}</div>
                                            <small class="text-muted">ID: {{ $role->id }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-light border">
                                        {{ $role->guard_name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-primary">
                                        {{ $role->permission_count }} permission
                                    </span>
                                </td>

                                <td>
                                    <span class="text-muted">
                                        {{ \Carbon\Carbon::parse($role->created_at)->format('d M Y') }}
                                    </span>
                                </td>

                                <td class="text-end pe-4">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $role->id }})"
                                        class="btn btn-sm btn-light border rounded-3"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
    type="button"
    onclick="confirmDeleteRole({{ $role->id }})"
    class="btn btn-sm btn-light border text-danger rounded-3"
>
    <i class="bi bi-trash"></i>
</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data role.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-white">
                {{ $roles->links() }}
            </div>
        </div>
    </div>

    <div
        class="modal fade @if($showModal) show d-block @endif"
        tabindex="-1"
        style="@if($showModal) background: rgba(15, 23, 42, .55); @else display: none; @endif"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">
                            {{ $isEdit ? 'Edit Role' : 'Tambah Role' }}
                        </h5>
                        <div class="text-muted small">
                            {{ $isEdit ? 'Update role dan permission akses.' : 'Buat role baru dan pilih permission.' }}
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
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Nama Role</label>

                            <input
                                type="text"
                                wire:model="name"
                                class="form-control rounded-3"
                                placeholder="Contoh: Admin Logistic"
                            >

                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Permission</label>

                            <div class="row g-2">
                                @foreach ($permissions as $permission)
                                    <div class="col-md-4">
                                        <label
                                            for="permission-{{ $permission->id }}"
                                            class="border rounded-3 p-3 w-100 d-flex align-items-start gap-2"
                                            style="cursor: pointer;"
                                        >
                                            <input
                                                class="form-check-input mt-1"
                                                type="checkbox"
                                                wire:model="selectedPermissions"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                            >

                                            <div>
                                                <div class="fw-semibold">
                                                    {{ $permission->name }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $permission->guard_name }}
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            @error('selectedPermissions')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
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
</div>

@script
<script>
    window.confirmDeleteRole = function (id) {
        Swal.fire({
            title: 'Hapus role?',
            text: 'Data role akan dihapus permanen.',
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
