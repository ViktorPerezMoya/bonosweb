<div>
    <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Total Empresas</h3>
            <div style="font-size: 2rem; font-weight: 700; color: white;">{{ \App\Models\Tenant::count() }}</div>
        </div>
        <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Empresas Activas</h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--success);">{{ \App\Models\Tenant::where('is_suspended', false)->count() }}</div>
        </div>
        <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Deuda Total</h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--warning);">$ {{ number_format(\App\Models\Tenant::sum('current_balance'), 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="glass-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Directorio de Clientes</h3>
            <button wire:click="openModal" class="btn btn-primary" style="background: var(--accent); font-size: 0.85rem;">
                <i class="ri-building-line" style="margin-right: 5px;"></i> Registrar Empresa
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Empresa / Subdominio</th>
                        <th>Saldo Corriente</th>
                        <th>Estado</th>
                        <th>Firma Electrónica</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr style="opacity: {{ $tenant->is_suspended ? '0.6' : '1' }}">
                        <td>
                            <strong style="display: block; font-size: 1rem;">{{ $tenant->company_name }}</strong>
                            <a href="http://{{ $tenant->domains->first()->domain ?? '#' }}" target="_blank" style="color: var(--accent); font-size: 0.85rem; text-decoration: none;">
                                {{ $tenant->domains->first()->domain ?? 'Sin dominio' }} <i class="ri-external-link-line"></i>
                            </a>
                        </td>
                        <td>
                            <span style="color: {{ ($tenant->current_balance ?? 0) > 0 ? 'var(--warning)' : 'var(--text-secondary)' }}; font-weight: {{ ($tenant->current_balance ?? 0) > 0 ? 'bold' : 'normal' }};">
                                $ {{ number_format($tenant->current_balance ?? 0, 2, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            @if($tenant->is_suspended)
                                <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: var(--danger);">Suspendida</span>
                            @else
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success);">Activa</span>
                            @endif
                        </td>
                        <td>
                            @if($tenant->cert_path && $tenant->cert_key_path)
                                <span style="color: var(--success); font-size: 0.85rem;"><i class="ri-shield-check-line"></i> Certificado OK</span>
                            @else
                                <button wire:click="generateCertificate('{{ $tenant->id }}')" class="btn" style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                    <i class="ri-key-2-line"></i> Generar
                                </button>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button wire:click="toggleSuspension('{{ $tenant->id }}')" class="btn" style="background: {{ $tenant->is_suspended ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $tenant->is_suspended ? 'var(--success)' : 'var(--danger)' }}; padding: 0.4rem 0.6rem;" title="{{ $tenant->is_suspended ? 'Activar' : 'Suspender' }}">
                                    <i class="ri-{{ $tenant->is_suspended ? 'play-circle-line' : 'pause-circle-line' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay empresas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $tenants->links() }}
        </div>
    </div>

    <!-- Modal Registrar Empresa -->
    @if($showModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 500px; padding: 2rem;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Registrar Nueva Empresa</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                Al registrar, el sistema creará automáticamente una Base de Datos exclusiva para este cliente.
            </p>
            <form wire:submit.prevent="createTenant">
                <div class="form-group">
                    <label class="form-label">Razón Social</label>
                    <input type="text" wire:model="company_name" class="form-control" placeholder="Ej: Coca-Cola FEMSA S.A." required>
                    @error('company_name') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Subdominio (Sin espacios)</label>
                    <div style="display: flex; align-items: center;">
                        <input type="text" wire:model="subdomain" class="form-control" placeholder="coca-cola" required style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        <div style="background: rgba(255,255,255,0.05); padding: 0.75rem 1rem; border: 1px solid var(--glass-border); border-left: none; border-top-right-radius: var(--radius-md); border-bottom-right-radius: var(--radius-md); color: var(--text-secondary);">
                            .{{ env('APP_ENV') === 'local' ? 'localhost' : 'bonosweb.com' }}
                        </div>
                    </div>
                    @error('subdomain') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; font-size: 0.95rem; color: var(--accent);">Datos del Administrador Inicial</h4>
                    <label class="form-label">Nombre del Administrador</label>
                    <input type="text" wire:model="admin_name" class="form-control" placeholder="Juan Pérez" required>
                    @error('admin_name') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico (Login)</label>
                    <input type="email" wire:model="admin_email" class="form-control" placeholder="juan.perez@empresa.com" required>
                    @error('admin_email') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" wire:model="admin_password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    @error('admin_password') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <button type="button" wire:click="closeModal" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">Crear Infraestructura</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
