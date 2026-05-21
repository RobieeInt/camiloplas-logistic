<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Users</h4>
            <div class="text-muted">Kelola user, role, dan akses logistic module.</div>
        </div>

        <button
            type="button"
            class="btn btn-primary rounded-3 px-4"
            wire:click="openCreateModal"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Tambah User
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
                                placeholder="Cari nama atau email user..."
                            >
                        </div>
                    </div>

                    <div class="col-md-6 text-md-end text-muted small">
                        Total data: {{ $users->total() }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end pe-4" width="180">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center"
                                            style="width: 42px; height: 42px;"
                                        >
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <small class="text-muted">
                                                Dibuat {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="text-muted">{{ $user->email }}</span>
                                </td>

                                <td>
                                    @if (!empty($user->role_names))
                                        @foreach (explode(',', $user->role_names) as $roleName)
                                            <span class="badge rounded-pill text-bg-light border me-1">
                                                {{ $roleName }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">
                                            No Role
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end pe-4">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $user->id }})"
                                        class="btn btn-sm btn-light border rounded-3"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                             <button
    type="button"
    onclick="confirmDeleteUser({{ $user->id }})"
    class="btn btn-sm btn-light border text-danger rounded-3"
>
    <i class="bi bi-trash"></i>
</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data user.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-white">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    {{-- Modal --}}
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
                            {{ $isEdit ? 'Edit User' : 'Tambah User' }}
                        </h5>
                        <div class="text-muted small">
                            {{ $isEdit ? 'Update data user dan role akses.' : 'Buat user baru untuk akses sistem.' }}
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
                                <label class="form-label fw-semibold">Nama</label>
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

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input
                                    type="email"
                                    wire:model="email"
                                    class="form-control rounded-3"
                                    placeholder="admin@camiloplas.com"
                                >
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">
                                    Password
                                    @if ($isEdit)
                                        <span class="text-muted fw-normal">(kosongkan kalau tidak diganti)</span>
                                    @endif
                                </label>

                                <input
                                    type="password"
                                    wire:model="password"
                                    class="form-control rounded-3"
                                    placeholder="{{ $isEdit ? 'Password baru opsional' : 'Minimal 6 karakter' }}"
                                >
                                @error('password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Role Akses</label>

                                <div class="row g-2">
                                    @foreach ($roles as $role)
                                        <div class="col-md-6">
                                            <label
                                                for="role-{{ $role->id }}"
                                                class="border rounded-3 p-3 w-100 d-flex align-items-center gap-2 cursor-pointer"
                                            >
                                                <input
                                                    class="form-check-input m-0"
                                                    type="checkbox"
                                                    wire:model="selectedRoles"
                                                    value="{{ $role->id }}"
                                                    id="role-{{ $role->id }}"
                                                >

                                                <span class="fw-semibold">
                                                    {{ $role->name }}
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                @error('selectedRoles')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
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
</div>
@script
<script>
    window.confirmDeleteUser = function (id) {
        Swal.fire({
            title: 'Hapus user?',
            text: 'User ini akan dihapus dari sistem.',
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
