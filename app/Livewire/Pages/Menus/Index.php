<?php

namespace App\Livewire\Pages\Menus;

use App\Services\Master\PermissionService;
use App\Services\System\MenuService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public ?int $menuId = null;
    public ?int $parentId = null;
    public string $name = '';
    public string $route = '';
    public string $icon = '';
    public string $permissionName = '';
    public int $sortOrder = 0;
    public bool $isActive = true;

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

    public function openEditModal(int $id, MenuService $menuService): void
    {
        $menu = $menuService->find($id);

        if (!$menu) {
            session()->flash('error', 'Menu tidak ditemukan.');
            return;
        }

        $this->menuId = $menu->id;
        $this->parentId = $menu->parent_id;
        $this->name = $menu->name;
        $this->route = $menu->route ?? '';
        $this->icon = $menu->icon ?? '';
        $this->permissionName = $menu->permission_name ?? '';
        $this->sortOrder = (int) $menu->sort_order;
        $this->isActive = (bool) $menu->is_active;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(MenuService $menuService): void
    {
        $this->validate([
            'parentId' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'permissionName' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'route' => $this->route,
            'icon' => $this->icon,
            'permission_name' => $this->permissionName,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->isEdit && $this->menuId) {
            if ($this->parentId === $this->menuId) {
                session()->flash('error', 'Menu tidak boleh jadi parent dirinya sendiri. Bahkan database juga punya harga diri.');
                return;
            }

            $menuService->update($this->menuId, $payload);
            session()->flash('success', 'Menu berhasil diupdate.');
        } else {
            $menuService->create($payload);
            session()->flash('success', 'Menu berhasil dibuat.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id, MenuService $menuService): void
    {
        $menu = $menuService->find($id);

        if (!$menu) {
            session()->flash('error', 'Menu tidak ditemukan.');
            return;
        }

        $menuService->delete($id);

        session()->flash('success', 'Menu berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'menuId',
            'parentId',
            'name',
            'route',
            'icon',
            'permissionName',
            'sortOrder',
            'isEdit',
        ]);

        $this->isActive = true;

        $this->resetValidation();
    }

    public function render(MenuService $menuService, PermissionService $permissionService)
    {
        return view('livewire.pages.menus.index', [
            'menus' => $menuService->paginate(
                search: $this->search,
                page: $this->getPage()
            ),
            'parents' => $menuService->parents($this->menuId),
            'permissions' => $permissionService->all(),
        ]);
    }
}
