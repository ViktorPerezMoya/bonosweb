<div>
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;">Seleccionar Liquidación</h3>

        <div style="max-width: 520px; position: relative;"
             x-data="{
                open: false,
                search: '',
                selectedId: {{ $selectedBatchId ?? 'null' }},
                hoveredId: null,
                batches: @js($batches->map(fn($b) => [
                    'id'    => $b->id,
                    'label' => $b->period_year
                               . ' — Mes ' . str_pad($b->period_month, 2, '0', STR_PAD_LEFT)
                               . ' (' . ucfirst($b->liquidation_type) . ')'
                               . ' · Subido el ' . $b->created_at->format('d/m/Y H:i'),
                ])->values()),
                get selectedLabel() {
                    const found = this.batches.find(b => b.id == this.selectedId);
                    return found ? found.label : '';
                },
                get filtered() {
                    const s = this.search.trim().toLowerCase();
                    return s ? this.batches.filter(b => b.label.toLowerCase().includes(s)) : this.batches;
                },
                select(id) {
                    this.selectedId = id;
                    $wire.set('selectedBatchId', id);
                    this.search = '';
                    this.open = false;
                },
             }"
             x-effect="if (open) $nextTick(() => $refs.searchInput && $refs.searchInput.focus())"
             @click.outside="open = false"
             @keydown.escape.window="open = false">

            {{-- Trigger --}}
            <div @click="open = !open"
                 style="display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.9rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: var(--radius-md); cursor: pointer; user-select: none; min-height: 2.5rem;"
                 :style="open ? 'border-color: var(--accent); box-shadow: 0 0 0 2px rgba(99,102,241,0.2);' : ''">
                <span style="font-size: 0.875rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 90%;"
                      :style="selectedLabel ? 'color: var(--text-primary);' : 'color: var(--text-secondary);'"
                      x-text="selectedLabel || 'Seleccione un lote...'"></span>
                <i class="ri-arrow-down-s-line"
                   style="color: var(--text-secondary); transition: transform 0.2s; flex-shrink: 0; margin-left: 0.5rem;"
                   :style="open ? 'transform: rotate(180deg);' : ''"></i>
            </div>

            {{-- Dropdown --}}
            <div x-show="open"
                 x-transition
                 style="position: relative; z-index: 50; width: 100%; margin-top: 4px;
                        background: #1a1f2e; border: 1px solid var(--glass-border);
                        border-radius: var(--radius-md); box-shadow: 0 8px 32px rgba(0,0,0,0.5);
                        overflow: hidden;">

                {{-- Buscador --}}
                <div style="padding: 0.5rem 0.5rem 0.35rem;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 0.35rem 0.65rem;">
                        <i class="ri-search-line" style="color: var(--text-secondary); font-size: 0.82rem; flex-shrink: 0;"></i>
                        <input x-ref="searchInput"
                               x-model="search"
                               type="text"
                               placeholder="Buscar por período, tipo..."
                               @click.stop
                               style="background: transparent; border: none; outline: none; color: var(--text-primary); font-size: 0.85rem; width: 100%;">
                    </div>
                </div>

                {{-- Lista de opciones --}}
                <div style="max-height: 260px; overflow-y: auto;">
                    <template x-for="batch in filtered" :key="batch.id">
                        <div @click="select(batch.id)"
                             :style="batch.id == selectedId
                                ? 'background: rgba(99,102,241,0.15); color: var(--accent);'
                                : hoveredId == batch.id
                                    ? 'background: rgba(255,255,255,0.06); color: var(--text-primary);'
                                    : 'color: var(--text-primary);'"
                             style="display: flex; align-items: center; justify-content: space-between; padding: 0.55rem 0.9rem; font-size: 0.85rem; cursor: pointer;"
                             @mouseenter="hoveredId = batch.id"
                             @mouseleave="hoveredId = null">
                            <span x-text="batch.label"></span>
                            <i x-show="batch.id == selectedId"
                               class="ri-check-line"
                               style="flex-shrink: 0; margin-left: 0.5rem; font-size: 0.9rem;"></i>
                        </div>
                    </template>
                    <div x-show="filtered.length === 0"
                         style="padding: 1rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">
                        Sin resultados para "<span x-text="search"></span>"
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($selectedBatchId)
        <div class="glass-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Detalle de Firmas del Lote</h3>
                <!-- Botón simulado para notificar a los pendientes -->
                <button class="btn btn-primary" onclick="alert('Funcionalidad para enviar email recordatorio a los pendientes.')">
                    <i class="ri-mail-send-line" style="margin-right: 5px;"></i> Notificar Pendientes
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <input type="text" wire:model.live="searchName" placeholder="Buscar por empleado..." class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-2 text-sm text-white focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select wire:model.live="searchStatus" class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-2 text-sm text-white focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendientes</option>
                        <option value="signed_conforme">Firmado Conforme</option>
                        <option value="signed_no_conforme">Firmado No Conforme</option>
                    </select>
                </div>
                <div>
                    <input type="date" wire:model.live="searchDate" class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-2 text-sm text-white focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Recibo (PDF)</th>
                            <th>Estado de Firma</th>
                            <th>Fecha y Hora</th>
                            <th>IP Dispositivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $payslip)
                        <tr>
                            <td>
                                <div style="font-weight: 500; color: var(--text-primary);">{{ $payslip->employee->name ?? 'Usuario Eliminado' }}</div>
                            </td>
                            <td>
                                <i class="ri-file-pdf-line" style="color: var(--danger); margin-right: 5px;"></i>
                                {{ $payslip->original_filename }}
                            </td>
                            <td>
                                @if($payslip->status === 'signed_conforme')
                                    <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded-full border border-emerald-500/30 font-medium inline-flex items-center">
                                        <i class="ri-shield-check-fill mr-1"></i> Conforme
                                    </span>
                                @elseif($payslip->status === 'signed_no_conforme')
                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full border border-red-500/30 font-medium inline-flex items-center">
                                        <i class="ri-error-warning-fill mr-1"></i> No Conforme
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-yellow-500/20 text-yellow-500 text-xs rounded-full border border-yellow-500/30 font-medium inline-flex items-center">
                                        <i class="ri-time-line mr-1"></i> Pendiente
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->signature)
                                    {{ $payslip->signature->signed_at->format('d/m/Y H:i:s') }}
                                @else
                                    <span style="color: var(--text-secondary);">-</span>
                                @endif
                            </td>
                            <td>
                                @if($payslip->signature)
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $payslip->signature->ip_address }}</span>
                                @else
                                    <span style="color: var(--text-secondary);">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                                No se encontraron recibos que coincidan con los filtros aplicados.
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
    @endif
</div>
