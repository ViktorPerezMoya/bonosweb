<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersManager extends Component
{
    use WithPagination;

    public $showModal = false;
    
    // User Form
    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'hr'; // 'admin' or 'hr'

    public function mount()
    {
        // Solo administradores pueden gestionar usuarios
        abort_if(auth()->user()->role !== 'admin', 403, 'Acceso denegado');
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'hr';
        $this->resetErrorBag();
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = ''; // Leave blank to not change
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function saveUser()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->userId,
            'role' => 'required|in:admin,hr',
        ];

        if (!$this->userId || $this->password) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            User::findOrFail($this->userId)->update($data);
            session()->flash('message', 'Usuario actualizado correctamente.');
        } else {
            User::create($data);
            session()->flash('message', 'Usuario creado exitosamente.');
        }

        $this->closeModal();
    }

    public function deleteUser($id)
    {
        // Prevenir auto-eliminación
        if ($id == auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propio usuario.');
            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('message', 'Usuario eliminado correctamente.');
    }

    public function render()
    {
        return view('livewire.tenant.users-manager', [
            'users' => User::whereIn('role', ['admin', 'hr'])->paginate(10)
        ])->layout('components.layouts.app', [
            'header' => 'Gestión de Personal Administrativo',
            'title' => 'Usuarios - RRHH'
        ]);
    }
}
