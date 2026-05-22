<div>
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Empleados Activos</h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary);">{{ $totalEmployees }}</div>
        </div>
        
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Lotes Procesados</h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--text-primary);">{{ $totalBatches }}</div>
        </div>
        
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Firmas Pendientes</h3>
            <div style="font-size: 2rem; font-weight: 700; color: var(--warning);">{{ $pendingSignatures }}</div>
        </div>
    </div>

    <!-- Latest Batch Tracking -->
    @if(isset($latestBatchStats))
    <div class="glass-panel" style="margin-bottom: 2rem; border-left: 4px solid var(--accent);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h3 style="margin-bottom: 0.25rem;">Última Liquidación: {{ $latestBatchStats['batch']->period_year }} - Mes {{ str_pad($latestBatchStats['batch']->period_month, 2, '0', STR_PAD_LEFT) }}</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary);">{{ ucfirst($latestBatchStats['batch']->liquidation_type) }} - Subido el {{ $latestBatchStats['batch']->created_at->format('d/m/Y') }}</p>
            </div>
            <a href="/reports/signatures" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">Ver Detalles</a>
        </div>
        
        <div style="display: flex; align-items: center; gap: 2rem;">
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-secondary);">
                    <span>Progreso de Firmas</span>
                    <span style="font-weight: 600; color: var(--text-primary);">{{ $latestBatchStats['percentage'] }}%</span>
                </div>
                <div style="width: 100%; background: rgba(255,255,255,0.1); height: 12px; border-radius: 6px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $latestBatchStats['percentage'] }}%; background: var(--success); transition: width 1s ease-in-out;"></div>
                </div>
            </div>
            <div style="display: flex; gap: 1.5rem; text-align: center;">
                <div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">{{ $latestBatchStats['signed'] }}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Firmados</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning);">{{ $latestBatchStats['pending'] }}</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Pendientes</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Batches Table -->
    <div class="glass-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Cargas Recientes</h3>
            <a href="/payslips/upload" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.5rem 1rem;"><i class="ri-upload-line" style="margin-right: 5px;"></i> Nueva Carga</a>
        </div>
        
        <div style="overflow-x: auto;">
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
                    <tr>
                        <td style="font-weight: 500;">
                            <i class="ri-file-zip-line" style="color: var(--text-secondary); margin-right: 5px;"></i> 
                            {{ $batch->original_filename }}
                        </td>
                        <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex-grow: 1; background: rgba(255,255,255,0.1); height: 6px; border-radius: 3px; overflow: hidden;">
                                    @php $percent = $batch->total_files > 0 ? ($batch->processed_files / $batch->total_files) * 100 : 0; @endphp
                                    <div style="height: 100%; width: {{ $percent }}%; background: var(--accent);"></div>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $batch->processed_files }}/{{ $batch->total_files }}</span>
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
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay lotes procesados todavía.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
