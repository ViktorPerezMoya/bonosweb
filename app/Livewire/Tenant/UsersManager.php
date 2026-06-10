<?php

namespace App\Livewire\Tenant;

use App\Models\Scopes\CurrentCompanyScope;
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

    public $initialCompanyId = null; // Para la creación

    // Assign Modal
    public $showAssignModal = false;
    public $assignUserId = null;
    public $assignedCompanies = [];
    public $activeCompanies = [];

    public function mount()
    {
        // Solo administradores pueden gestionar usuarios
        abort_if(auth()->user()->role !== 'admin', 403, 'Acceso denegado');
        $this->activeCompanies = \App\Models\Company::where('is_active', true)->get();
    }

    public function openModal()
    {
        $this->resetForm();
        // Si hay empresas activas, preseleccionar la primera (o la principal)
        $mainOrFirst = $this->activeCompanies->firstWhere('is_main', true) ?? $this->activeCompanies->first();
        if ($mainOrFirst) {
            $this->initialCompanyId = $mainOrFirst->id;
        }
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
        $this->initialCompanyId = null;
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

        if ($this->role === 'hr' && !$this->userId) {
            $rules['initialCompanyId'] = 'required|exists:companies,id';
        }

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
            $user = User::create($data);
            if ($this->role === 'hr' && $this->initialCompanyId) {
                $user->accessibleCompanies()->sync([$this->initialCompanyId]);
            }
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

    public function openAssignModal($id)
    {
        $user = User::findOrFail($id);
        $this->assignUserId = $user->id;
        $this->assignedCompanies = $user->accessibleCompanies()->pluck('company_id')->map(fn($id) => (string)$id)->toArray();
        $this->showAssignModal = true;
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->assignUserId = null;
        $this->assignedCompanies = [];
    }

    public function saveAssignments()
    {
        $user = User::findOrFail($this->assignUserId);
        $user->accessibleCompanies()->sync($this->assignedCompanies);
        session()->flash('message', 'Accesos granulares actualizados para ' . $user->name);
        $this->closeAssignModal();
    }

    public function render()
    {
        return view('livewire.tenant.users-manager', [
            // withoutGlobalScope garantiza que se listen TODOS los usuarios de
            // gestión del tenant, sin importar qué empresa esté activa en sesión.
            'users' => User::withoutGlobalScope(CurrentCompanyScope::class)
                           ->whereIn('role', ['admin', 'hr'])
                           ->paginate(10)
        ])->layout('components.layouts.app', [
            'header' => 'Gestión de Personal Administrativo',
            'title' => 'Usuarios - RRHH'
        ]);
    }
}
