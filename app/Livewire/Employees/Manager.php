<?php

namespace App\Livewire\Employees;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Services\CompanyContextService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class Manager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;

    // Importación de Empleados Existentes
    public $showImportModal = false;
    public $searchImport = '';

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
        $profileId = $this->isEditing ? EmployeeProfile::where('user_id', $this->userId)->first()->id ?? null : null;
        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($this->userId)
            ],
            'cuil' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('employee_profiles', 'cuil')
                    ->ignore($profileId)
                    ->where('company_id', $companyId)
            ],
            'document_number' => 'nullable|string',
            'department' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchImport()
    {
        // Se autodispara al escribir en el campo de búsqueda de importación
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

    public function openImportModal()
    {
        $this->searchImport = '';
        $this->showImportModal = true;
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->searchImport = '';
    }

    public function resetForm()
    {
        $this->reset(['userId', 'name', 'email', 'cuil', 'document_number', 'department', 'is_active', 'isEditing']);
        $this->resetValidation();
    }

    public function edit($id)
    {
        $user    = User::findOrFail($id);
        $profile = EmployeeProfile::where('user_id', $user->id)->first();

        $this->userId = $user->id;
        $this->name   = $user->name;
        $this->email  = $user->email;

        if ($profile) {
            $this->cuil            = $profile->cuil;
            $this->document_number = $profile->document_number;
            $this->department      = $profile->department;
            $this->is_active       = $profile->is_active;
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

            EmployeeProfile::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'company_id' => app(CompanyContextService::class)->getCurrentCompanyId(),
                ],
                [
                    'cuil'            => $this->cuil,
                    'document_number' => $this->document_number,
                    'department'      => $this->department,
                    'is_active'       => $this->is_active,
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
                'user_id'         => $user->id,
                'company_id'      => app(CompanyContextService::class)->getCurrentCompanyId(),
                'cuil'            => $this->cuil,
                'document_number' => $this->document_number,
                'department'      => $this->department,
                'is_active'       => $this->is_active,
            ]);

            \App\Jobs\GenerateEmployeeCertificate::dispatch($employeeProfile->id);

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

    public function importUser($userId)
    {
        $user = User::withoutGlobalScopes()->findOrFail($userId);
        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();

        // Evitar doble importación
        $exists = EmployeeProfile::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->exists();

        if ($exists) {
            session()->flash('message', 'El empleado ya está registrado en esta empresa.');
            return;
        }

        // Buscar un perfil existente en otra empresa para copiar el CUIL y DNI
        $existingProfile = EmployeeProfile::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        $profile = EmployeeProfile::create([
            'user_id'         => $user->id,
            'company_id'      => $companyId,
            'cuil'            => $existingProfile ? $existingProfile->cuil : '',
            'document_number' => $existingProfile ? $existingProfile->document_number : null,
            'department'      => null, // Departamento por defecto vacío en nueva empresa
            'is_active'       => true,
        ]);

        \App\Jobs\GenerateEmployeeCertificate::dispatch($profile->id);

        $this->closeImportModal();
        session()->flash('message', 'Empleado vinculado exitosamente a esta empresa.');
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

                    $profile = EmployeeProfile::updateOrCreate(
                        [
                            'user_id'    => $user->id,
                            'company_id' => app(CompanyContextService::class)->getCurrentCompanyId(),
                        ],
                        [
                            'cuil'            => $cuil,
                            'document_number' => $dni,
                            'department'      => $depto,
                            'is_active'       => true,
                        ]
                    );

                    if ($profile->wasRecentlyCreated || !$profile->certificate_path) {
                        \App\Jobs\GenerateEmployeeCertificate::dispatch($profile->id);
                    }

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
        $currentCompanyId = app(CompanyContextService::class)->getCurrentCompanyId();

        $employees = User::where('role', 'employee')
            ->whereHas('currentCompanyProfile', function($query) use ($currentCompanyId) {
                // Aislamos estrictamente a la empresa activa
                $query->where('company_id', $currentCompanyId);
            })
            ->with(['currentCompanyProfile'])
            ->when($this->search, function ($query) {
                $query->where(function($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                             ->orWhere('email', 'like', '%' . $this->search . '%')
                             ->orWhereHas('currentCompanyProfile', function ($q) {
                                 $q->where('cuil', 'like', '%' . $this->search . '%');
                             });
                });
            })
            ->paginate(10);

        // Resultados para el modal de Importación (Búsqueda global en el Tenant)
        $importCandidates = [];
        if ($this->showImportModal && strlen($this->searchImport) >= 3) {
            $importCandidates = User::withoutGlobalScopes()
                ->where('role', 'employee')
                ->whereDoesntHave('employeeProfiles', function ($query) use ($currentCompanyId) {
                    // Excluimos los que ya tienen perfil en ESTA empresa
                    $query->where('company_id', $currentCompanyId);
                })
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->searchImport . '%')
                          ->orWhere('email', 'like', '%' . $this->searchImport . '%')
                          ->orWhereHas('employeeProfiles', function ($sub) {
                              $sub->withoutGlobalScopes()
                                  ->where('cuil', 'like', '%' . $this->searchImport . '%')
                                  ->orWhere('document_number', 'like', '%' . $this->searchImport . '%');
                          });
                })
                ->with(['employeeProfiles' => function($q) {
                    $q->withoutGlobalScopes();
                }])
                ->take(5)
                ->get();
        }

        return view('livewire.employees.manager', [
            'employees' => $employees,
            'importCandidates' => $importCandidates
        ])->layout('components.layouts.app', [
            'header' => 'Gestión de Empleados',
            'title' => 'Empleados - BonosWeb'
        ]);
    }
}
