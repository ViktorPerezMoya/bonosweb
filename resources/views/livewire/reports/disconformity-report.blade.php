<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 500; color: var(--text-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-error-warning-line" style="color: var(--accent);"></i>
                Auditoría de Disconformidades
            </h2>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">
                Consulta y exportación de recibos firmados en disconformidad. 
                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: var(--accent); margin-left: 0.5rem;">
                    Total: {{ $payslips->total() }} registros
                </span>
            </p>
        </div>

        <button wire:click="exportToExcel" wire:loading.attr="disabled" class="btn btn-primary" style="background: rgba(16, 185, 129, 1); box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);">
            <i class="ri-file-excel-line" style="margin-right: 5px;"></i> Exportar a Excel
            <span wire:loading wire:target="exportToExcel">
                <i class="ri-loader-4-line" style="animation: spin 1s linear infinite; margin-left: 5px;"></i>
            </span>
        </button>
    </div>

    <!-- Barra de Filtros -->
    <div class="glass-panel" style="margin-bottom: 1.5rem; padding: 1.5rem;">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label style="display: block; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Empleado</label>
                <input type="text" wire:model.live="searchEmployee" placeholder="Buscar por nombre..." class="w-full form-control">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Motivo de Disconformidad</label>
                <select wire:model.live="searchReason" class="w-full form-control">
                    <option value="">Todos los motivos</option>
                    @foreach($reasons as $reason)
                        <option value="{{ $reason->id }}">{{ $reason->reason_text }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Año</label>
                <input type="number" wire:model.live="searchYear" placeholder="Ej: {{ date('Y') }}" class="w-full form-control">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Mes</label>
                <select wire:model.live="searchMonth" class="w-full form-control">
                    <option value="">Todos los meses</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="glass-panel">
        <div style="overflow-x: auto;">
            <table class="modern-table w-full text-left">
                <thead>
                    <tr>
                        <th wire:click="sortBy('employee.name')" style="cursor: pointer; user-select: none;">
                            EMPLEADO
                            @if($sortField === 'employee.name')
                                <i class="{{ $sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('liquidation_type')" style="cursor: pointer; user-select: none;">
                            LIQUIDACIÓN
                            @if($sortField === 'liquidation_type')
                                <i class="{{ $sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('period_year')" style="cursor: pointer; user-select: none;">
                            PERÍODO
                            @if($sortField === 'period_year')
                                <i class="{{ $sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('disagreementReason.reason')" style="cursor: pointer; user-select: none;">
                            MOTIVO DE NO CONFORMIDAD
                            @if($sortField === 'disagreementReason.reason')
                                <i class="{{ $sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('upload_batch_id')" style="cursor: pointer; user-select: none;">
                            LOTE ORIGEN
                            @if($sortField === 'upload_batch_id')
                                <i class="{{ $sortDirection === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $payslip)
                    <tr>
                        <td style="font-weight: 500;">
                            @if($payslip->employee)
                                <a href="{{ route('employees.history', $payslip->employee->id) }}" class="text-accent hover:underline" style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                    {{ $payslip->employee->name }}
                                    <i class="ri-external-link-line" style="font-size: 0.85rem; opacity: 0.7;"></i>
                                </a>
                            @else
                                <span style="color: var(--text-secondary);">Usuario Eliminado</span>
                            @endif
                        </td>
                        <td style="text-transform: capitalize;">
                            {{ $payslip->liquidation_type }}
                        </td>
                        <td>
                            {{ $payslip->period_year }} - Mes {{ str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td>
                            @if($payslip->disagreementReason)
                                <span style="color: var(--danger); font-weight: 500;">
                                    {{ $payslip->disagreementReason->reason }}
                                </span>
                            @else
                                <span style="color: var(--warning); font-weight: 500;">
                                    Otro / No especificado
                                </span>
                            @endif
                            
                            @if($payslip->disconformity_reason)
                                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.2rem; max-width: 300px; white-space: normal;">
                                    "{{ $payslip->disconformity_reason }}"
                                </p>
                            @endif
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            <i class="ri-folder-zip-line" style="margin-right: 5px;"></i>
                            Lote #{{ $payslip->upload_batch_id }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                            <i class="ri-file-search-line" style="font-size: 2rem; display: block; margin-bottom: 1rem; color: var(--glass-border);"></i>
                            No se encontraron recibos firmados en disconformidad con estos filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $payslips->links() }}
        </div>
    </div>
</div>
