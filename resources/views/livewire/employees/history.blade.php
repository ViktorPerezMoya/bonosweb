<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <a href="/employees" class="btn" style="background: rgba(255,255,255,0.1); color: var(--text-primary);">
            <i class="ri-arrow-left-line" style="margin-right: 5px;"></i> Volver
        </a>
        
        <a href="{{ route('employees.export-history', ['id' => $employeeId]) }}" target="_blank" class="btn btn-primary" style="background: var(--success); box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.39);">
            <i class="ri-file-shield-2-line" style="margin-right: 5px;"></i> Exportar Auditoría Firmada
        </a>
    </div>

    <div class="glass-panel" style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 2rem; align-items: center;">
            <i class="ri-user-settings-line" style="font-size: 3rem; color: var(--accent);"></i>
            <div>
                <h3 style="margin-bottom: 0.25rem;">{{ $employee->name }}</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                    CUIL: <strong style="color: var(--text-primary);">{{ $employee->employeeProfile->cuil ?? 'N/A' }}</strong> | 
                    Email: <strong style="color: var(--text-primary);">{{ $employee->email }}</strong> |
                    Depto: <strong style="color: var(--text-primary);">{{ $employee->employeeProfile->department ?? 'N/A' }}</strong>
                </p>
            </div>
        </div>
    </div>

    <div class="glass-panel">
        <h3 style="margin-bottom: 1.5rem;">Recibos de Sueldo</h3>
        
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Tipo</th>
                        <th>Archivo</th>
                        <th>Conformidad</th>
                        <th>Fecha de Firma</th>
                        <th>Auditoría IP</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $payslip)
                    <tr>
                        <td style="font-weight: 500;">
                            {{ $payslip->period_year }} - Mes {{ str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td style="text-transform: capitalize;">{{ $payslip->liquidation_type }}</td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $payslip->original_filename }}
                        </td>
                        <td>
                            @if($payslip->signature)
                                <span class="badge badge-success"><i class="ri-check-line"></i> Firmado</span>
                            @else
                                <span class="badge badge-pending"><i class="ri-time-line"></i> Pendiente</span>
                            @endif
                        </td>
                        <td style="font-size: 0.85rem;">
                            {{ $payslip->signature ? $payslip->signature->signed_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $payslip->signature ? $payslip->signature->ip_address : '-' }}
                        </td>
                        <td>
                            <button wire:click="viewPdf({{ $payslip->id }})" class="btn" style="background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                <i class="ri-eye-line" style="margin-right: 5px;"></i> Ver
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                            El empleado no tiene recibos asignados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PDF Modal -->
    @if($showPdfModal)
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; display: flex; flex-direction: column;">
        <div style="padding: 1rem 2rem; background: var(--bg-secondary); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border);">
            <h3 style="margin: 0; color: white;">Visor de Recibo Original</h3>
            <div style="display: flex; gap: 1rem;">
                <a href="{{ $selectedPdfUrl }}?download=1" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;"><i class="ri-download-line"></i> Descargar</a>
                <button wire:click="closePdfModal" style="background: none; border: none; color: white; font-size: 2rem; cursor: pointer; line-height: 1;">&times;</button>
            </div>
        </div>
        <div style="flex-grow: 1; padding: 2rem; display: flex; justify-content: center;">
            <iframe src="{{ $selectedPdfUrl }}" style="width: 100%; max-width: 1000px; height: 100%; border: none; border-radius: var(--radius-md); background: white;"></iframe>
        </div>
    </div>
    @endif
</div>
