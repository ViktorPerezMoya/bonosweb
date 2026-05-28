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
                        <th style="text-align: center;">Empleados Activos</th>
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
                        <td style="text-align: center;">
                            <span style="font-size: 1.1rem; font-weight: 700; color: white;">{{ $employeeCounts[$tenant->id] ?? 0 }}</span>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">activos</div>
                        </td>
                        <td>
                            @if($tenant->cert_path && $tenant->cert_key_path)
                                @php
                                    $expiry    = $tenant->cert_expiry;
                                    $daysLeft  = $expiry ? (int) now()->diffInDays($expiry, false) : null;
                                    $isExpired = $daysLeft !== null && $daysLeft < 0;
                                    $isSoon    = $daysLeft !== null && $daysLeft >= 0 && $daysLeft < 90;
                                @endphp
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    {{-- Badge de estado --}}
                                    @if($isExpired)
                                        <span style="color: var(--danger); font-size: 0.82rem; font-weight: 500;">
                                            <i class="ri-error-warning-line"></i> Expirado
                                        </span>
                                        @if($expiry)
                                            <span style="color: var(--danger); font-size: 0.7rem; opacity: 0.8;">
                                                Venció {{ $expiry->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    @elseif($isSoon)
                                        <span style="color: var(--warning); font-size: 0.82rem; font-weight: 500;">
                                            <i class="ri-alarm-warning-line"></i> Por vencer
                                        </span>
                                        <span style="color: var(--warning); font-size: 0.7rem; opacity: 0.85;">
                                            En {{ $daysLeft }} días ({{ $expiry->format('d/m/Y') }})
                                        </span>
                                    @else
                                        <span style="color: var(--success); font-size: 0.82rem; font-weight: 500;">
                                            <i class="ri-shield-check-line"></i> Certificado OK
                                        </span>
                                        @if($expiry)
                                            <span style="color: var(--text-secondary); font-size: 0.7rem;">
                                                Vence {{ $expiry->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    @endif

                                    {{-- Botón Renovar --}}
                                    <button
                                        wire:click="regenerateCertificate('{{ $tenant->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="regenerateCertificate('{{ $tenant->id }}')"
                                        class="btn"
                                        style="background: {{ $isExpired ? 'rgba(239,68,68,0.15)' : ($isSoon ? 'rgba(245,158,11,0.15)' : 'rgba(99,102,241,0.12)') }}; color: {{ $isExpired ? 'var(--danger)' : ($isSoon ? 'var(--warning)' : '#a5b4fc') }}; padding: 0.25rem 0.55rem; font-size: 0.72rem; margin-top: 2px;">
                                        <span wire:loading.remove wire:target="regenerateCertificate('{{ $tenant->id }}')">
                                            <i class="ri-refresh-line"></i> Renovar
                                        </span>
                                        <span wire:loading wire:target="regenerateCertificate('{{ $tenant->id }}')">
                                            <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                                        </span>
                                    </button>
                                </div>
                            @else
                                <button
                                    wire:click="generateCertificate('{{ $tenant->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="generateCertificate('{{ $tenant->id }}')"
                                    class="btn"
                                    style="background: rgba(245, 158, 11, 0.2); color: var(--warning); padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                    <span wire:loading.remove wire:target="generateCertificate('{{ $tenant->id }}')">
                                        <i class="ri-key-2-line"></i> Generar
                                    </span>
                                    <span wire:loading wire:target="generateCertificate('{{ $tenant->id }}')">
                                        <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                                    </span>
                                </button>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button wire:click="openEditModal('{{ $tenant->id }}')" class="btn" style="background: rgba(99, 102, 241, 0.15); color: #a5b4fc; padding: 0.4rem 0.6rem;" title="Editar empresa">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button wire:click="toggleSuspension('{{ $tenant->id }}')" class="btn" style="background: {{ $tenant->is_suspended ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $tenant->is_suspended ? 'var(--success)' : 'var(--danger)' }}; padding: 0.4rem 0.6rem;" title="{{ $tenant->is_suspended ? 'Activar' : 'Suspender' }}">
                                    <i class="ri-{{ $tenant->is_suspended ? 'play-circle-line' : 'pause-circle-line' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay empresas registradas.</td>
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
                    <label class="form-label">CUIT del Empleador</label>
                    <input type="text" wire:model="employer_cuit" class="form-control" placeholder="Ej: 30712345678" maxlength="11" inputmode="numeric">
                    <small style="color: var(--text-secondary); font-size: 0.78rem;">11 dígitos sin guiones. Se usa para identificar los bonos al procesarlos.</small>
                    @error('employer_cuit') <span style="color: var(--danger); font-size: 0.85rem; display: block;">{{ $message }}</span> @enderror
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

    <!-- Modal Editar Empresa -->
    @if($showEditModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 520px; padding: 2rem; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-top: 0; margin-bottom: 0.25rem;">Editar Empresa</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">Modificá los datos de la empresa y del administrador.</p>

            <form wire:submit.prevent="saveTenantEdit">

                <!-- Datos de la empresa -->
                <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--glass-border);">
                    <h4 style="margin: 0 0 1rem; font-size: 0.95rem; color: var(--accent);">
                        <i class="ri-building-line" style="margin-right: 5px;"></i>Datos de la Empresa
                    </h4>
                    <div class="form-group">
                        <label class="form-label">Razón Social</label>
                        <input type="text" wire:model="editCompanyName" class="form-control" placeholder="Ej: Coca-Cola FEMSA S.A." required>
                        @error('editCompanyName') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">CUIT del Empleador</label>
                        <input type="text" wire:model="editEmployerCuit" class="form-control" placeholder="Ej: 30712345678" maxlength="11" inputmode="numeric">
                        <small style="color: var(--text-secondary); font-size: 0.78rem;">11 dígitos sin guiones.</small>
                        @error('editEmployerCuit') <span style="color: var(--danger); font-size: 0.85rem; display: block;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Datos del administrador -->
                <div>
                    <h4 style="margin: 1rem 0; font-size: 0.95rem; color: var(--accent);">
                        <i class="ri-user-settings-line" style="margin-right: 5px;"></i>Administrador de la Empresa
                    </h4>
                    <div class="form-group">
                        <label class="form-label">Nombre</label>
                        <input type="text" wire:model="editAdminName" class="form-control" placeholder="Juan Pérez" required>
                        @error('editAdminName') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico (Login)</label>
                        <input type="email" wire:model="editAdminEmail" class="form-control" placeholder="juan.perez@empresa.com" required>
                        @error('editAdminEmail') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña <span style="color: var(--text-secondary); font-weight: 400; font-size: 0.8rem;">(dejar vacío para no cambiar)</span></label>
                        <input type="password" wire:model="editAdminPassword" class="form-control" placeholder="Mínimo 6 caracteres">
                        @error('editAdminPassword') <span style="color: var(--danger); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                    <button type="button" wire:click="closeEditModal" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">
                        <i class="ri-save-line" style="margin-right: 5px;"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
