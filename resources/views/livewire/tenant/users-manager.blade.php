<div>
    @if (session()->has('message'))
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i class="ri-checkbox-circle-line"></i> {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <i class="ri-error-warning-line"></i> {{ session('error') }}
        </div>
    @endif

    <div class="glass-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Usuarios Administrativos</h3>
            <button wire:click="openModal" class="btn btn-primary" style="background: var(--accent); font-size: 0.85rem;">
                <i class="ri-user-add-line" style="margin-right: 5px;"></i> Nuevo Usuario
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Rol / Permisos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <strong style="display: block; font-size: 1rem;">{{ $user->name }}</strong>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: var(--accent);">Administrador Global</span>
                            @else
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success);">Personal RRHH</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button wire:click="editUser({{ $user->id }})" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-primary); padding: 0.4rem 0.6rem;" title="Editar">
                                    <i class="ri-edit-line"></i>
                                </button>
                                @if($user->id !== auth()->id())
                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="¿Estás seguro de eliminar este usuario?" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.4rem 0.6rem;" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay usuarios registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Modal Usuario -->
    @if($showModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 500px; padding: 2rem;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem;">{{ $userId ? 'Editar Usuario' : 'Registrar Nuevo Usuario' }}</h3>
            <form wire:submit.prevent="saveUser">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" wire:model="name" class="form-control" required>
                    @error('name') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" wire:model="email" class="form-control" required>
                    @error('email') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nivel de Acceso</label>
                    <select wire:model="role" class="form-control" required>
                        <option value="hr">Personal RRHH (Solo subir/ver recibos)</option>
                        <option value="admin">Administrador (Puede crear más usuarios)</option>
                    </select>
                    @error('role') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña {{ $userId ? '(Dejar en blanco para no cambiar)' : '' }}</label>
                    <input type="password" wire:model="password" class="form-control" {{ $userId ? '' : 'required' }}>
                    @error('password') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <button type="button" wire:click="closeModal" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">{{ $userId ? 'Actualizar' : 'Registrar' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
