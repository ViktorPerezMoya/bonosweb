<div>
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h3 style="margin-bottom: 0.25rem;">SuperAdministradores</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Usuarios con acceso total a la gestión de Tenants.</p>
            </div>
            <button wire:click="openModal" class="btn btn-primary" style="font-size: 0.85rem;">
                <i class="ri-user-add-line" style="margin-right: 5px;"></i> Nuevo Administrador
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Fecha de Alta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $admin)
                    <tr>
                        <td style="font-weight: 500;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                    {{ substr($admin->name, 0, 1) }}
                                </div>
                                {{ $admin->name }}
                                @if(auth()->id() == $admin->id)
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success);">Tú</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $admin->email }}</td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $admin->created_at->format('d/m/Y') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button wire:click="edit({{ $admin->id }})" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-primary); padding: 0.4rem 0.6rem;" title="Editar">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button wire:click="delete({{ $admin->id }})" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.4rem 0.6rem;" title="Eliminar" onclick="confirm('¿Estás seguro de eliminar este administrador?') || event.stopImmediatePropagation()">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $admins->links() }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($showModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 500px; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">{{ $isEditing ? 'Editar Administrador' : 'Nuevo Administrador' }}</h3>
                <button wire:click="closeModal" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <form wire:submit.prevent="save">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" wire:model="name" class="form-control" required>
                    @error('name') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" wire:model="email" class="form-control" required>
                    @error('email') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña {{ $isEditing ? '(Dejar en blanco para no cambiar)' : '' }}</label>
                    <input type="password" wire:model="password" class="form-control" {{ $isEditing ? '' : 'required' }}>
                    @error('password') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <button type="button" wire:click="closeModal" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
