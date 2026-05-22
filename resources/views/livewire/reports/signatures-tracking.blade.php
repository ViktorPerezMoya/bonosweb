<div>
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;">Seleccionar Liquidación</h3>
        <div class="form-group" style="max-width: 500px;">
            <select wire:model.live="selectedBatchId" class="form-control">
                <option value="">Seleccione un lote...</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}">
                        {{ $batch->period_year }} - Mes {{ str_pad($batch->period_month, 2, '0', STR_PAD_LEFT) }} 
                        ({{ ucfirst($batch->liquidation_type) }}) - Subido el {{ $batch->created_at->format('d/m/Y') }}
                    </option>
                @endforeach
            </select>
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
                                @if($payslip->signature)
                                    <span class="badge badge-success"><i class="ri-checkbox-circle-line"></i> Firmado</span>
                                @else
                                    <span class="badge badge-pending"><i class="ri-time-line"></i> Pendiente</span>
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
                                No hay recibos válidos en este lote.
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
