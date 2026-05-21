<?php

namespace App\Livewire\Pages\Roles;

use App\Services\Master\PermissionService;
use App\Services\Master\RoleService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public ?int $roleId = null;
    public string $name = '';
    public array $selectedPermissions = [];

    public bool $isEdit = false;
    public bool $showModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id, RoleService $roleService): void
    {
        $role = $roleService->find($id);

        if (!$role) {
            session()->flash('error', 'Role tidak ditemukan.');
            return;
        }

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $roleService->getRolePermissionIds($id);
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(RoleService $roleService): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'selectedPermissions' => ['array'],
        ];

        $this->validate($rules);

        if ($this->isEdit && $this->roleId) {
            $roleService->update($this->roleId, $this->name);
            $roleService->syncPermissions($this->roleId, $this->selectedPermissions);

            session()->flash('success', 'Role berhasil diupdate.');
        } else {
            $newRoleId = $roleService->create($this->name);
            $roleService->syncPermissions($newRoleId, $this->selectedPermissions);

            session()->flash('success', 'Role berhasil dibuat.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id, RoleService $roleService): void
    {
        $role = $roleService->find($id);

        if (!$role) {
            session()->flash('error', 'Role tidak ditemukan.');
            return;
        }

        if ($role->name === 'Super Admin') {
            session()->flash('error', 'Role Super Admin tidak boleh dihapus. Jangan ngerusak fondasi rumah sendiri, bray.');
            return;
        }

        if ($roleService->roleIsUsed($id)) {
            session()->flash('error', 'Role masih dipakai user, jadi belum bisa dihapus.');
            return;
        }

        $roleService->delete($id);

        session()->flash('success', 'Role berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'roleId',
            'name',
            'selectedPermissions',
            'isEdit',
        ]);

        $this->resetValidation();
    }

    public function render(RoleService $roleService, PermissionService $permissionService)
    {
        return view('livewire.pages.roles.index', [
            'roles' => $roleService->paginate(
                search: $this->search,
                page: $this->getPage()
            ),
            'permissions' => $permissionService->all(),
        ]);
    }
}
