<?php

namespace App\Livewire\Employees;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class Manager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    
    public $csvFile;

    // Form fields
    public $userId;
    public $name;
    public $email;
    public $cuil;
    public $document_number;
    public $department;
    public $is_active = true;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'cuil' => 'required|string|unique:employee_profiles,cuil,' . ($this->isEditing ? EmployeeProfile::where('user_id', $this->userId)->first()->id ?? 'NULL' : 'NULL'),
            'document_number' => 'nullable|string',
            'department' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

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
        $this->reset(['userId', 'name', 'email', 'cuil', 'document_number', 'department', 'is_active', 'isEditing']);
        $this->resetValidation();
    }

    public function edit($id)
    {
        $user = User::with('employeeProfile')->findOrFail($id);
        
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        
        if ($user->employeeProfile) {
            $this->cuil = $user->employeeProfile->cuil;
            $this->document_number = $user->employeeProfile->document_number;
            $this->department = $user->employeeProfile->department;
            $this->is_active = $user->employeeProfile->is_active;
        }

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            $user->employeeProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cuil' => $this->cuil,
                    'document_number' => $this->document_number,
                    'department' => $this->department,
                    'is_active' => $this->is_active,
                ]
            );

            session()->flash('message', 'Empleado actualizado correctamente.');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->cuil), // Contraseña por defecto: CUIL
                'role' => 'employee'
            ]);

            $employeeProfile = EmployeeProfile::create([
                'user_id' => $user->id,
                'cuil' => $this->cuil,
                'document_number' => $this->document_number,
                'department' => $this->department,
                'is_active' => $this->is_active,
            ]);

            // Sincronizar con Base Central (Identidad Global)
            $globalUser = \App\Models\GlobalUser::firstOrCreate(
                ['cuil' => $this->cuil],
                [
                    'dni' => $this->document_number,
                    'email' => $this->email,
                    'password' => Hash::make($this->cuil)
                ]
            );
            $globalUser->tenants()->syncWithoutDetaching([tenant('id')]);

            session()->flash('message', 'Empleado registrado. La contraseña inicial es su número de CUIL.');
        }

        $this->closeModal();
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(10);
        
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        session()->flash('message', "Contraseña reseteada. Nueva contraseña para {$user->email}: {$newPassword}");
    }

    public function toggleActive($id)
    {
        $profile = EmployeeProfile::where('user_id', $id)->first();
        if ($profile) {
            $profile->update(['is_active' => !$profile->is_active]);
            $status = $profile->is_active ? 'activado' : 'suspendido';
            session()->flash('message', "El empleado ha sido {$status}.");
        }
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:5120', // Max 5MB
        ]);

        $path = $this->csvFile->getRealPath();
        
        $lines = file($path);
        // Detectar si el delimitador es punto y coma (Excel en español) o coma
        $delimiter = (isset($lines[0]) && strpos($lines[0], ';') !== false) ? ';' : ',';
        
        $data = array_map(function($line) use ($delimiter) {
            return str_getcsv($line, $delimiter);
        }, $lines);
        
        // Asumimos que la fila 1 es la cabecera (Nombre, Email, CUIL, DNI, Depto)
        array_shift($data); 
        
        $imported = 0;
        foreach($data as $row) {
            // Validar que la fila tenga al menos Nombre, Email y CUIL
            if(count($row) >= 3) {
                $name = trim($row[0]);
                $email = trim($row[1]);
                $cuil = preg_replace('/[^0-9]/', '', $row[2]); // Extraer solo números
                $dni = isset($row[3]) ? trim($row[3]) : null;
                $depto = isset($row[4]) ? trim($row[4]) : null;

                if(!empty($email) && !empty($cuil)) {
                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $name,
                            'password' => Hash::make($cuil),
                            'role' => 'employee'
                        ]
                    );

                    $user->employeeProfile()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'cuil' => $cuil,
                            'document_number' => $dni,
                            'department' => $depto,
                            'is_active' => true
                        ]
                    );

                    // Sincronizar con Base Central (Identidad Global)
                    $globalUser = \App\Models\GlobalUser::firstOrCreate(
                        ['cuil' => $cuil],
                        [
                            'dni' => $dni,
                            'email' => $email,
                            'password' => Hash::make($cuil)
                        ]
                    );
                    $globalUser->tenants()->syncWithoutDetaching([tenant('id')]);

                    $imported++;
                }
            }
        }

        session()->flash('message', "Se importaron/actualizaron {$imported} empleados exitosamente.");
        $this->reset('csvFile');
    }

    public function render()
    {
        $employees = User::where('role', 'employee')
            ->with('employeeProfile')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhereHas('employeeProfile', function ($q) {
                          $q->where('cuil', 'like', '%' . $this->search . '%');
                      });
            })
            ->paginate(10);

        return view('livewire.employees.manager', [
            'employees' => $employees
        ])->layout('components.layouts.app', [
            'header' => 'Gestión de Empleados',
            'title' => 'Empleados - BonosWeb'
        ]);
    }
}
