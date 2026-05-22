<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User; // Usamos el User central
use Illuminate\Support\Facades\Hash;

class AdminsManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $userId;
    public $name = '';
    public $email = '';
    public $password = '';
    public $isEditing = false;

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            if (!empty($this->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save();
            session()->flash('message', 'Administrador actualizado exitosamente.');
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'admin'
            ]);
            session()->flash('message', 'Administrador creado exitosamente.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        if (User::count() <= 1) {
            session()->flash('error', 'No puedes eliminar al único administrador del sistema.');
            return;
        }

        if (auth()->id() == $id) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta mientras estás logueado.');
            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('message', 'Administrador eliminado.');
    }

    public function render()
    {
        return view('livewire.superadmin.admins-manager', [
            'admins' => User::paginate(10)
        ])->layout('components.layouts.superadmin', [
            'header' => 'Gestión de Administradores',
            'title' => 'Admins - BonosWeb Central'
        ]);
    }
}
