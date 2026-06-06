<div>
    <div class="glass-panel">

        @if (session()->has('message'))
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="ri-information-line" style="font-size: 1.2rem;"></i>
                {{ session('message') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:gap-3 order-first sm:order-last">
                <button wire:click="openImportModal" class="btn" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: var(--text-primary); width: 100%; justify-content: center;">
                    <i class="ri-user-received-line" style="margin-right: 5px;"></i> Importar de otra Empresa
                </button>
                <button wire:click="openModal" class="btn btn-primary w-full sm:w-auto" style="justify-content: center;">
                    <i class="ri-user-add-line" style="margin-right: 5px;"></i> Nuevo Empleado
                </button>
            </div>
            <div class="w-full sm:flex-1 sm:max-w-sm">
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por nombre, email o CUIL..." style="width: 100%;">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>CUIL</th>
                        <th>Departamento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <div style="font-weight: 500; color: var(--text-primary);">{{ $employee->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $employee->email }}</div>
                        </td>
                        <td>{{ $employee->currentCompanyProfile->cuil ?? 'No asignado' }}</td>
                        <td>{{ $employee->currentCompanyProfile->department ?? '-' }}</td>
                        <td>
                            @if(optional($employee->currentCompanyProfile)->is_active)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-error">Suspendido</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="/employees/{{ $employee->id }}/history" class="btn" style="background: rgba(59, 130, 246, 0.2); color: var(--accent); padding: 0.4rem 0.6rem;" title="Ver Historial">
                                    <i class="ri-file-history-line"></i>
                                </a>
                                <button wire:click="edit({{ $employee->id }})" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-primary); padding: 0.4rem 0.6rem;" title="Editar">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button wire:click="resetPassword({{ $employee->id }})" class="btn" style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 0.4rem 0.6rem;" title="Resetear Clave" onclick="confirm('¿Estás seguro de generar una nueva contraseña temporal?') || event.stopImmediatePropagation()">
                                    <i class="ri-key-2-line"></i>
                                </button>
                                <button wire:click="toggleActive({{ $employee->id }})" class="btn" style="background: {{ optional($employee->currentCompanyProfile)->is_active ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)' }}; color: {{ optional($employee->currentCompanyProfile)->is_active ? 'var(--danger)' : 'var(--success)' }}; padding: 0.4rem 0.6rem;" title="{{ optional($employee->currentCompanyProfile)->is_active ? 'Suspender' : 'Activar' }}">
                                    <i class="ri-shut-down-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                            <i class="ri-group-line" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                            No hay empleados registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $employees->links() }}
        </div>

        <div style="margin-top: 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-top: 2rem; border-top: 1px dashed var(--glass-border);">
            <form wire:submit.prevent="importCsv" style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                <div style="position: relative; overflow: hidden; display: inline-block;">
                    <button type="button" class="btn btn-csv" style="font-size: 0.85rem;">
                        <i class="ri-file-excel-2-line" style="margin-right: 5px;"></i> Subir Empleados por CSV
                    </button>
                    <input type="file" wire:model="csvFile" accept=".csv" style="position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;">
                </div>
                @if($csvFile)
                    <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;" wire:loading.attr="disabled" wire:target="importCsv">
                        <span wire:loading.remove wire:target="importCsv">Importar</span>
                        <span wire:loading wire:target="importCsv">...</span>
                    </button>
                @endif
            </form>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px; text-align: center;">
                <i class="ri-information-line"></i> Formato requerido de columnas: <br>
                <code style="background: rgba(0,0,0,0.2); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); margin-top: 0.5rem; display: inline-block;">nombre, email, cuil, dni, departamento</code>
            </div>
        </div>
    </div>

    <!-- Modal Form (CSS only approach for simplicity without external JS libraries) -->
    @if($showModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 100; display: flex; align-items: center; justify-content: center;">
        <div class="glass-panel" style="width: 100%; max-width: 500px; padding: 2rem; background: var(--bg-secondary); overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">{{ $isEditing ? 'Editar Empleado' : 'Registrar Empleado' }}</h3>
                <button wire:click="closeModal" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <form wire:submit.prevent="save">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" wire:model="name" class="form-control">
                    @error('name') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" wire:model="email" class="form-control">
                    @error('email') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">CUIL (Sin guiones)</label>
                        <input type="text" wire:model="cuil" class="form-control" placeholder="20123456789">
                        @error('cuil') <span style="color: var(--danger); font-size: 0.8rem;">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">DNI</label>
                        <input type="text" wire:model="document_number" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Departamento / Área</label>
                    <input type="text" wire:model="department" class="form-control" placeholder="Ej: Ventas">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <button type="button" wire:click="closeModal" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-primary);">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Datos</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Import Modal -->
    @if($showImportModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 100; display: flex; align-items: center; justify-content: center;">
        <div class="glass-panel" style="width: 100%; max-width: 600px; padding: 2rem; background: var(--bg-secondary); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;"><i class="ri-search-line" style="margin-right: 5px;"></i> Importar Empleado</h3>
                <button wire:click="closeImportModal" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                Busca un empleado existente en tu corporación por nombre, email, CUIL o DNI para asignarlo también a esta empresa.
            </p>

            <div class="form-group">
                <input type="text" wire:model.live.debounce.300ms="searchImport" class="form-control" placeholder="Escribe al menos 3 caracteres...">
            </div>

            <div style="flex: 1; overflow-y: auto; margin-top: 1rem; border: 1px solid var(--glass-border); border-radius: var(--radius-md); background: rgba(0,0,0,0.2);">
                @if(strlen($searchImport) >= 3)
                    @if(count($importCandidates) > 0)
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach($importCandidates as $candidate)
                                @php
                                    $prof = $candidate->employeeProfiles->first();
                                @endphp
                                <li style="padding: 1rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: bold; color: var(--text-primary);">{{ $candidate->name }}</div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                            {{ $candidate->email }} | CUIL: {{ $prof ? $prof->cuil : 'N/A' }}
                                        </div>
                                    </div>
                                    <button wire:click="importUser({{ $candidate->id }})" class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;" wire:loading.attr="disabled" wire:target="importUser">
                                        Importar
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                            No se encontraron empleados no vinculados con esos datos.
                        </div>
                    @endif
                @else
                    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                        Escribe para comenzar a buscar...
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
