<div>
    <div class="glass-panel">
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="order-first sm:order-last">
                <a href="/payslips/upload" class="btn btn-primary w-full sm:w-auto">
                    <i class="ri-upload-line" style="margin-right: 5px;"></i> Subir Nuevo Lote
                </a>
            </div>
            <div class="w-full sm:flex-1 sm:max-w-sm">
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por nombre de archivo..." style="width: 100%;">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Archivo</th>
                        <th>Subido por</th>
                        <th>Fecha</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Detalles / Errores</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr>
                        <td style="color: var(--text-secondary);">#{{ $batch->id }}</td>
                        <td style="font-weight: 500;">
                            <i class="ri-file-zip-line" style="color: var(--accent); margin-right: 5px;"></i>
                            {{ $batch->original_filename }}
                        </td>
                        <td style="color: var(--text-secondary);">{{ $batch->uploader->name ?? 'Sistema' }}</td>
                        <td>{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                                    <span>Procesados: {{ $batch->processed_files }}</span>
                                    <span>Total: {{ $batch->total_files }}</span>
                                </div>
                                <div style="width: 100%; background: rgba(255,255,255,0.1); height: 6px; border-radius: 3px; overflow: hidden;">
                                    @php $percent = $batch->total_files > 0 ? ($batch->processed_files / $batch->total_files) * 100 : 0; @endphp
                                    <div style="height: 100%; width: {{ $percent }}%; background: var(--success); transition: width 0.5s;"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($batch->status == 'completed')
                                <span class="badge badge-success">Completado</span>
                            @elseif($batch->status == 'processing')
                                <span class="badge badge-pending">Procesando</span>
                            @elseif($batch->status == 'pending')
                                <span class="badge badge-pending" style="background: rgba(255,255,255,0.1); color: var(--text-secondary); border-color: var(--text-secondary);">En Cola</span>
                            @else
                                <span class="badge badge-error">Fallido</span>
                            @endif
                        </td>
                        <td>
                            @if($batch->error_log)
                                <div style="max-height: 60px; overflow-y: auto; font-size: 0.75rem; color: var(--danger); background: rgba(239, 68, 68, 0.05); padding: 5px; border-radius: 4px; border-left: 2px solid var(--danger);">
                                    {!! nl2br(e($batch->error_log)) !!}
                                </div>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-secondary);"><i class="ri-check-double-line" style="color: var(--success);"></i> Sin errores</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                            <i class="ri-inbox-line" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                            No se encontraron lotes de recibos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $batches->links() }}
        </div>
    </div>
</div>
