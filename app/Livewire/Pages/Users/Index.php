<?php

namespace App\Livewire\Pages\Users;

use App\Services\Auth\UserService;
use App\Services\Master\RoleService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';

    public array $selectedRoles = [];

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

    public function openEditModal(int $id, UserService $userService): void
    {
        $user = $userService->find($id);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $userService->getUserRoleIds($id);
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(UserService $userService): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'selectedRoles' => ['array'],
        ];

        if ($this->isEdit) {
            $rules['password'] = ['nullable', 'min:6'];
        } else {
            $rules['password'] = ['required', 'min:6'];
        }

        $validated = $this->validate($rules);

        if ($this->isEdit && $this->userId) {
            $userService->update($this->userId, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $this->password,
            ]);

            $userService->syncRoles($this->userId, $this->selectedRoles);

            session()->flash('success', 'User berhasil diupdate.');
        } else {
            $newUserId = $userService->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $userService->syncRoles($newUserId, $this->selectedRoles);

            session()->flash('success', 'User berhasil dibuat.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id, UserService $userService): void
    {
        if (auth()->id() === $id) {
            session()->flash('error', 'User yang sedang login tidak boleh dihapus. Ya masa ngehapus diri sendiri, ini bukan film sci-fi murahan.');
            return;
        }

        $userService->delete($id);

        session()->flash('success', 'User berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'userId',
            'name',
            'email',
            'password',
            'selectedRoles',
            'isEdit',
        ]);

        $this->resetValidation();
    }

    public function render(UserService $userService, RoleService $roleService)
    {
        return view('livewire.pages.users.index', [
            'users' => $userService->paginate($this->search),
            'roles' => $roleService->all(),
        ]);
    }
}
