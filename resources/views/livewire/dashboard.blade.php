<div>
    {{-- ── Stats Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        <div class="glass-panel flex items-start gap-4" style="padding: 1.25rem 1.5rem;">
            <div class="flex-shrink-0 flex items-center justify-center w-11 h-11 rounded-xl"
                 style="background: rgba(59,130,246,0.15);">
                <i class="ri-team-line text-accent text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-muted mb-1" style="margin:0 0 0.25rem;">Empleados Activos</p>
                <div class="text-3xl font-bold text-canvas">{{ $totalEmployees }}</div>
            </div>
        </div>

        <div class="glass-panel flex items-start gap-4" style="padding: 1.25rem 1.5rem;">
            <div class="flex-shrink-0 flex items-center justify-center w-11 h-11 rounded-xl"
                 style="background: rgba(16,185,129,0.15);">
                <i class="ri-stack-line text-success text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-muted mb-1" style="margin:0 0 0.25rem;">Lotes Procesados</p>
                <div class="text-3xl font-bold text-canvas">{{ $totalBatches }}</div>
            </div>
        </div>

        <div class="glass-panel flex items-start gap-4" style="padding: 1.25rem 1.5rem;">
            <div class="flex-shrink-0 flex items-center justify-center w-11 h-11 rounded-xl"
                 style="background: rgba(245,158,11,0.15);">
                <i class="ri-time-line text-warning text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-muted mb-1" style="margin:0 0 0.25rem;">Firmas Pendientes</p>
                <div class="text-3xl font-bold text-warning">{{ $pendingSignatures }}</div>
            </div>
        </div>

    </div>

    {{-- ── Última Liquidación ── --}}
    @if(isset($latestBatchStats))
    <div class="glass-panel mb-6" style="border-left: 4px solid var(--accent);">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 style="margin-bottom: 0.25rem;">Última Liquidación: {{ $latestBatchStats['batch']->period_year }} — Mes {{ str_pad($latestBatchStats['batch']->period_month, 2, '0', STR_PAD_LEFT) }}</h3>
                <p class="text-sm text-muted" style="margin:0;">{{ ucfirst($latestBatchStats['batch']->liquidation_type) }} · Subido el {{ $latestBatchStats['batch']->created_at->format('d/m/Y') }}</p>
            </div>
            <a href="/reports/signatures" class="btn btn-primary w-full sm:w-auto shrink-0"
               style="padding: 0.65rem 1.1rem; font-size: 0.875rem;">
                <i class="ri-bar-chart-line" style="margin-right: 6px;"></i> Ver Detalles
            </a>
        </div>

        {{-- Progreso + Contadores --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <div class="flex-1">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-muted">Progreso de Firmas</span>
                    <span class="font-semibold text-canvas">{{ $latestBatchStats['percentage'] }}%</span>
                </div>
                <div class="w-full h-3 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.1);">
                    <div class="h-full rounded-full"
                         style="width: {{ $latestBatchStats['percentage'] }}%; background: var(--success); transition: width 1s ease-in-out;"></div>
                </div>
            </div>
            <div class="flex items-center gap-6 text-center shrink-0">
                <div>
                    <div class="text-2xl font-bold text-success">{{ $latestBatchStats['signed'] }}</div>
                    <div class="text-xs text-muted uppercase tracking-wider" style="margin-top: 2px;">Firmados</div>
                </div>
                <div class="w-px h-10" style="background: var(--glass-border);"></div>
                <div>
                    <div class="text-2xl font-bold text-warning">{{ $latestBatchStats['pending'] }}</div>
                    <div class="text-xs text-muted uppercase tracking-wider" style="margin-top: 2px;">Pendientes</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Cargas Recientes ── --}}
    <div class="glass-panel">
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <h3 style="margin: 0;">Cargas Recientes</h3>
            <a href="/payslips/upload" class="btn btn-primary w-full sm:w-auto"
               style="padding: 0.65rem 1.1rem; font-size: 0.875rem;">
                <i class="ri-upload-line" style="margin-right: 6px;"></i> Nueva Carga
            </a>
        </div>

        {{-- Cards (móvil) --}}
        <div class="flex flex-col gap-3 md:hidden">
            @forelse($recentBatches as $batch)
            @php $percent = $batch->total_files > 0 ? ($batch->processed_files / $batch->total_files) * 100 : 0; @endphp
            <div class="rounded-xl p-4 border border-white/10" style="background: rgba(255,255,255,0.03);">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="ri-file-zip-line text-muted shrink-0"></i>
                        <span class="text-sm font-medium text-canvas truncate">{{ $batch->original_filename }}</span>
                    </div>
                    @if($batch->status == 'completed')
                        <span class="badge badge-success shrink-0">Completado</span>
                    @elseif($batch->status == 'processing')
                        <span class="badge badge-pending shrink-0">Procesando</span>
                    @else
                        <span class="badge badge-error shrink-0">Error</span>
                    @endif
                </div>
                <p class="text-xs text-muted" style="margin: 0 0 0.6rem;">{{ $batch->created_at->format('d/m/Y H:i') }}</p>
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.1);">
                        <div class="h-full rounded-full" style="width: {{ $percent }}%; background: var(--accent);"></div>
                    </div>
                    <span class="text-xs text-muted shrink-0">{{ $batch->processed_files }}/{{ $batch->total_files }}</span>
                </div>
            </div>
            @empty
            <p class="text-center text-muted py-8" style="margin:0;">No hay lotes procesados todavía.</p>
            @endforelse
        </div>

        {{-- Tabla (escritorio) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Archivo ZIP</th>
                        <th>Fecha</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBatches as $batch)
                    @php $percent = $batch->total_files > 0 ? ($batch->processed_files / $batch->total_files) * 100 : 0; @endphp
                    <tr>
                        <td style="font-weight: 500;">
                            <i class="ri-file-zip-line text-muted" style="margin-right: 6px;"></i>{{ $batch->original_filename }}
                        </td>
                        <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.1);">
                                    <div class="h-full rounded-full" style="width: {{ $percent }}%; background: var(--accent);"></div>
                                </div>
                                <span class="text-xs text-muted">{{ $batch->processed_files }}/{{ $batch->total_files }}</span>
                            </div>
                        </td>
                        <td>
                            @if($batch->status == 'completed')
                                <span class="badge badge-success">Completado</span>
                            @elseif($batch->status == 'processing')
                                <span class="badge badge-pending">Procesando...</span>
                            @else
                                <span class="badge badge-error">Error</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted" style="padding: 2.5rem;">No hay lotes procesados todavía.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
