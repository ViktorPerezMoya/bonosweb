<div>

    {{-- ════════════════════════════════════════════════════════════════════
         KPIs: apiladas en móvil, 3 columnas en md+
         ════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
        <div class="glass-panel p-5">
            <h3 class="text-sm font-medium mb-2" style="color: var(--text-secondary);">Total Empresas</h3>
            <div class="text-3xl font-bold text-white">{{ \App\Models\Tenant::count() }}</div>
        </div>
        <div class="glass-panel p-5">
            <h3 class="text-sm font-medium mb-2" style="color: var(--text-secondary);">Empresas Activas</h3>
            <div class="text-3xl font-bold" style="color: var(--success);">{{ \App\Models\Tenant::where('is_suspended', false)->count() }}</div>
        </div>
        <div class="glass-panel p-5">
            <h3 class="text-sm font-medium mb-2" style="color: var(--text-secondary);">Deuda Total</h3>
            <div class="text-3xl font-bold" style="color: var(--warning);">$ {{ number_format(\App\Models\Tenant::sum('current_balance'), 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="glass-panelmargin: 1rem;">

        {{-- ── Cabecera: título + búsqueda + botón nuevo ──────────────────── --}}
        {{-- En móvil: apilados (flex-col). En escritorio: misma fila (md:flex-row) --}}
        <div class="flex flex-col gap-3 mb-6 md:flex-row md:items-center md:justify-between">
            <h3 class="m-0 text-lg font-semibold text-white">Directorio de Clientes</h3>
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                       style="color: var(--text-secondary);"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="form-control w-full pl-9 md:w-72"
                        placeholder="Buscar empresa…"
                    >
                </div>
                <button wire:click="openModal"
                        class="btn btn-primary w-full md:w-auto whitespace-nowrap"
                        style="background: var(--accent);">
                    <i class="ri-building-line" style="margin-right: 5px;"></i> Nueva Empresa
                </button>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════
             TABLA: visible solo en escritorio (hidden → md:block)
             ════════════════════════════════════════════════════════════════ --}}
        <div class="hidden md:block">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Empresa / Subdominio</th>
                        <th>Saldo Corriente</th>
                        <th>Estado</th>
                        <th style="text-align: center;">Empleados Únicos</th>
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
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">únicos activos</div>
                        </td>
                        <td>
                            <button
                                wire:click="openCompanyCertModal('{{ $tenant->id }}')"
                                wire:loading.attr="disabled"
                                wire:target="openCompanyCertModal('{{ $tenant->id }}')"
                                class="btn"
                                style="background: rgba(99,102,241,0.12); color: #a5b4fc; padding: 0.3rem 0.6rem; font-size: 0.78rem;">
                                <span wire:loading.remove wire:target="openCompanyCertModal('{{ $tenant->id }}')">
                                    <i class="ri-key-2-line"></i> Gestionar Firmas
                                </span>
                                <span wire:loading wire:target="openCompanyCertModal('{{ $tenant->id }}')">
                                    <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                                </span>
                            </button>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button wire:click="openEditModal('{{ $tenant->id }}')" class="btn" style="background: rgba(99, 102, 241, 0.15); color: #a5b4fc; padding: 0.4rem 0.6rem;" title="Editar empresa">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button wire:click="toggleSuspension('{{ $tenant->id }}')" class="btn" style="background: {{ $tenant->is_suspended ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $tenant->is_suspended ? 'var(--success)' : 'var(--danger)' }}; padding: 0.4rem 0.6rem;" title="{{ $tenant->is_suspended ? 'Activar' : 'Suspender' }}">
                                    <i class="ri-{{ $tenant->is_suspended ? 'play-circle-line' : 'pause-circle-line' }}"></i>
                                </button>
                                <button
                                    wire:click="confirmTenantDeletion('{{ $tenant->id }}')"
                                    class="btn"
                                    style="background: rgba(239,68,68,0.12); color: var(--danger); padding: 0.4rem 0.6rem;"
                                    title="Eliminar permanentemente">
                                    <i class="ri-delete-bin-line"></i>
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

        {{-- ════════════════════════════════════════════════════════════════
             TARJETAS MÓVIL: visibles solo en pantallas pequeñas (md:hidden)
             Patrón "Table-to-Card" — botones táctiles min-h-[44px]
             ════════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @forelse($tenants as $tenant)
            <div class="glass-panel rounded-xl p-4"
                 style="border: 1px solid var(--glass-border);
                        opacity: {{ $tenant->is_suspended ? '0.75' : '1' }};">

                {{-- Encabezado: nombre + badge de estado --}}
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <h4 class="font-bold text-white text-base leading-tight mb-0.5">
                            {{ $tenant->company_name }}
                        </h4>
                        <a href="http://{{ $tenant->domains->first()->domain ?? '#' }}"
                           target="_blank"
                           class="text-xs inline-flex items-center gap-1"
                           style="color: var(--accent);">
                            {{ $tenant->domains->first()->domain ?? 'Sin dominio' }}
                            <i class="ri-external-link-line"></i>
                        </a>
                    </div>
                    @if($tenant->is_suspended)
                        <span class="badge shrink-0" style="background: rgba(239,68,68,0.2); color: var(--danger);">Suspendida</span>
                    @else
                        <span class="badge shrink-0" style="background: rgba(16,185,129,0.2); color: var(--success);">Activa</span>
                    @endif
                </div>

                {{-- Métricas en rejilla de 2 columnas --}}
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                    <div>
                        <dt class="text-xs" style="color: var(--text-secondary);">Saldo corriente</dt>
                        <dd class="font-semibold"
                            style="color: {{ ($tenant->current_balance ?? 0) > 0 ? 'var(--warning)' : 'white' }};">
                            $ {{ number_format($tenant->current_balance ?? 0, 2, ',', '.') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs" style="color: var(--text-secondary);">Empleados únicos</dt>
                        <dd class="font-semibold text-white">{{ $employeeCounts[$tenant->id] ?? 0 }}</dd>
                    </div>
                </dl>

                {{-- ── Acciones táctiles (min-h-[44px] / py-3 para facilidad de uso) ── --}}
                <div class="flex flex-col gap-2 pt-3 border-t border-white/10">

                    {{-- Firmas digitales --}}
                    <button
                        wire:click="openCompanyCertModal('{{ $tenant->id }}')"
                        wire:loading.attr="disabled"
                        wire:target="openCompanyCertModal('{{ $tenant->id }}')"
                        class="btn w-full py-3"
                        style="background: rgba(99,102,241,0.15); color: #a5b4fc;
                               justify-content: center; min-height: 44px;">
                        <span wire:loading.remove wire:target="openCompanyCertModal('{{ $tenant->id }}')">
                            <i class="ri-key-2-line" style="margin-right: 5px;"></i> Gestionar Firmas
                        </span>
                        <span wire:loading wire:target="openCompanyCertModal('{{ $tenant->id }}')">
                            <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                        </span>
                    </button>

                    {{-- Editar + Suspender / Activar (misma fila) --}}
                    <div class="flex gap-2">
                        <button
                            wire:click="openEditModal('{{ $tenant->id }}')"
                            class="btn flex-1 py-3"
                            style="background: rgba(99,102,241,0.15); color: #a5b4fc;
                                   justify-content: center; min-height: 44px;"
                            title="Editar empresa">
                            <i class="ri-edit-line" style="margin-right: 5px;"></i> Editar
                        </button>
                        <button
                            wire:click="toggleSuspension('{{ $tenant->id }}')"
                            class="btn flex-1 py-3"
                            style="background: {{ $tenant->is_suspended ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' }};
                                   color: {{ $tenant->is_suspended ? 'var(--success)' : 'var(--danger)' }};
                                   justify-content: center; min-height: 44px;"
                            title="{{ $tenant->is_suspended ? 'Activar empresa' : 'Suspender empresa' }}">
                            <i class="ri-{{ $tenant->is_suspended ? 'play-circle-line' : 'pause-circle-line' }}"
                               style="margin-right: 5px;"></i>
                            {{ $tenant->is_suspended ? 'Activar' : 'Suspender' }}
                        </button>
                    </div>

                    {{-- Eliminar permanentemente --}}
                    <button
                        wire:click="confirmTenantDeletion('{{ $tenant->id }}')"
                        class="btn w-full py-3"
                        style="background: rgba(239,68,68,0.12); color: var(--danger);
                               justify-content: center; min-height: 44px;"
                        title="Eliminar permanentemente">
                        <i class="ri-delete-bin-line" style="margin-right: 5px;"></i> Eliminar Permanentemente
                    </button>

                </div>
            </div>
            @empty
            <div class="glass-panel p-8 text-center" style="color: var(--text-secondary);">
                No hay empresas que coincidan con la búsqueda.
            </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        <div class="mt-6">
            {{ $tenants->links() }}
        </div>
    </div>

    <!-- Modal Registrar Empresa -->
    @if($showModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel scroll-styled" style="width: 100%; max-width: 500px; padding: 2rem;margin: 1rem; max-height: 85vh; overflow-y: auto;">
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
                    <button type="button" wire:click="closeModal"
                            wire:loading.attr="disabled" wire:target="createTenant"
                            class="btn" style="background: rgba(255,255,255,0.1);">
                        Cancelar
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="createTenant"
                            class="btn btn-primary"
                            style="background: var(--accent); min-width: 170px;">
                        <span wire:loading.remove wire:target="createTenant">
                            <i class="ri-building-line" style="margin-right: 5px;"></i>Crear Infraestructura
                        </span>
                        <span wire:loading wire:target="createTenant">
                            <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite; margin-right: 6px;"></i>Creando instancia…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal Editar Empresa -->
    @if($showEditModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel scroll-styled" style="width: 100%; max-width: 520px; padding: 2rem; max-height: 90vh; overflow-y: auto; margin: 1rem;">
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

    <!-- Modal Gestión de Firmas Digitales por Subempresa -->
    @if($showCompanyCertModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 1050;">
        <div class="glass-panel scroll-styled" style="width: 100%; max-width: 560px; padding: 2rem; max-height: 85vh; overflow-y: auto; margin: 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="margin: 0 0 0.25rem; font-size: 1.1rem;">Firmas Digitales</h3>
                    <p style="margin: 0; font-size: 0.82rem; color: var(--text-secondary);">
                        Tenant: <strong>{{ $certTenantId }}</strong> — Certificados PFX por subempresa
                    </p>
                </div>
                <button wire:click="closeCompanyCertModal" class="btn" style="background: rgba(255,255,255,0.08); color: var(--text-secondary); padding: 0.35rem 0.7rem;">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            @if(count($tenantCompanies) === 0)
                <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">
                    Este tenant no tiene subempresas configuradas aún.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($tenantCompanies as $company)
                    <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;">
                        {{-- Avatar inicial --}}
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(99,102,241,0.2); color: #a5b4fc; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; flex-shrink: 0;">
                            {{ strtoupper(substr($company['name'], 0, 1)) }}
                        </div>

                        {{-- Info empresa --}}
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="font-weight: 600; font-size: 0.95rem;">{{ $company['name'] }}</span>
                                @if($company['is_main'])
                                    <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; font-size: 0.7rem; padding: 1px 6px; border-radius: 20px;">Principal</span>
                                @endif
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">CUIT: {{ $company['cuit'] }}</div>
                            <div style="margin-top: 0.3rem;">
                                @if($company['has_cert'])
                                    <span style="color: var(--success); font-size: 0.78rem; font-weight: 500;">
                                        <i class="ri-shield-check-line"></i> Certificado PFX activo
                                    </span>
                                @else
                                    <span style="color: var(--warning); font-size: 0.78rem;">
                                        <i class="ri-error-warning-line"></i> Sin certificado
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div style="flex-shrink: 0;">
                            @if($company['has_cert'])
                                <button
                                    wire:click="regenerateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="regenerateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})"
                                    class="btn"
                                    style="background: rgba(99,102,241,0.12); color: #a5b4fc; padding: 0.3rem 0.65rem; font-size: 0.78rem;"
                                    title="Renovar certificado digital">
                                    <span wire:loading.remove wire:target="regenerateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})">
                                        <i class="ri-refresh-line"></i> Renovar
                                    </span>
                                    <span wire:loading wire:target="regenerateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})">
                                        <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                                    </span>
                                </button>
                            @else
                                <button
                                    wire:click="generateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="generateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})"
                                    class="btn"
                                    style="background: rgba(245,158,11,0.15); color: var(--warning); padding: 0.3rem 0.65rem; font-size: 0.78rem;"
                                    title="Generar nuevo certificado digital">
                                    <span wire:loading.remove wire:target="generateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})">
                                        <i class="ri-key-2-line"></i> Generar
                                    </span>
                                    <span wire:loading wire:target="generateCompanyCert('{{ $certTenantId }}', {{ $company['id'] }})">
                                        <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite;"></i>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            @if(session('message'))
                <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: var(--radius-md); color: var(--success); font-size: 0.85rem;">
                    <i class="ri-checkbox-circle-line" style="margin-right: 5px;"></i>{{ session('message') }}
                </div>
            @endif

            <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                <button wire:click="closeCompanyCertModal" class="btn" style="background: rgba(255,255,255,0.1);">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         Modal Borrado Total (Hard Delete) — Acción irreversible
         ═══════════════════════════════════════════════════════════════════════ --}}
    @if($showDeleteModal)
    <div
        x-data="{ confirmWord: @entangle('deleteConfirmationInput').live }"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%;
               background: rgba(0,0,0,0.75); backdrop-filter: blur(8px);
               display: flex; align-items: center; justify-content: center; z-index: 1100;"
    >
        <div class="glass-panel" style="width: 100%; max-width: 480px; padding: 2rem; border: 1px solid rgba(239,68,68,0.35);margin: 1rem;">

            {{-- Cabecera de peligro --}}
            <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="flex-shrink: 0; width: 48px; height: 48px; border-radius: 50%;
                            background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center;">
                    <i class="ri-alarm-warning-line" style="font-size: 1.6rem; color: var(--danger);"></i>
                </div>
                <div>
                    <h3 style="margin: 0 0 0.3rem; color: var(--danger); font-size: 1.05rem;">Eliminar Empresa Permanentemente</h3>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">
                        Esta acción <strong style="color: white;">no se puede deshacer</strong>. Se eliminarán:
                    </p>
                </div>
            </div>

            {{-- Lista de consecuencias --}}
            <ul style="margin: 0 0 1.5rem 0; padding: 0; list-style: none;
                       background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,0.2);
                       border-radius: var(--radius-md); padding: 1rem 1rem 1rem 1.25rem;">
                @foreach([
                    ['ri-database-2-line', "La base de datos completa de <strong>{$deleteTenantName}</strong>"],
                    ['ri-folder-3-line',   'Todos los recibos, logos y certificados .pfx del tenant'],
                    ['ri-file-shield-2-line', 'Los certificados CRT/KEY del panel central'],
                    ['ri-building-line',   'El registro en la tabla tenants y sus dominios'],
                ] as [$icon, $text])
                <li style="display: flex; align-items: center; gap: 0.6rem; padding: 0.3rem 0; font-size: 0.85rem; color: #fca5a5;">
                    <i class="{{ $icon }}" style="font-size: 1rem; flex-shrink: 0;"></i>
                    <span>{!! $text !!}</span>
                </li>
                @endforeach
            </ul>

            {{-- Input de confirmación estricta --}}
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="font-size: 0.85rem;">
                    Para confirmar, escribí exactamente: <strong style="color: var(--danger); letter-spacing: 0.05em;">ELIMINAR</strong>
                </label>
                <input
                    type="text"
                    x-model="confirmWord"
                    wire:model.live="deleteConfirmationInput"
                    class="form-control"
                    placeholder="ELIMINAR"
                    autocomplete="off"
                    spellcheck="false"
                    style="border-color: rgba(239,68,68,0.4); letter-spacing: 0.05em;"
                >
                @error('deleteConfirmationInput')
                    <span style="color: var(--danger); font-size: 0.8rem; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Acciones --}}
            <div style="display: flex; gap: 0.75rem;">
                <button
                    type="button"
                    wire:click="cancelTenantDeletion"
                    wire:loading.attr="disabled" wire:target="deleteTenant"
                    class="btn"
                    style="flex: 1; background: rgba(255,255,255,0.08); justify-content: center;">
                    Cancelar
                </button>
                <button
                    type="button"
                    wire:click="deleteTenant"
                    wire:loading.attr="disabled" wire:target="deleteTenant"
                    :disabled="confirmWord !== 'ELIMINAR'"
                    class="btn"
                    style="flex: 1; background: var(--danger); color: white; justify-content: center;
                           opacity: 1; transition: opacity 0.2s;"
                    :style="confirmWord !== 'ELIMINAR' ? 'opacity: 0.35; cursor: not-allowed;' : 'opacity: 1; cursor: pointer;'"
                >
                    <span wire:loading.remove wire:target="deleteTenant">
                        <i class="ri-delete-bin-line" style="margin-right: 5px;"></i>Eliminar Todo
                    </span>
                    <span wire:loading wire:target="deleteTenant">
                        <i class="ri-loader-4-line" style="display: inline-block; animation: spin 1s linear infinite; margin-right: 6px;"></i>Eliminando…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
