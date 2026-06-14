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
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="display: flex; gap: 2rem; align-items: center;">
                <i class="ri-user-settings-line" style="font-size: 3rem; color: var(--accent);"></i>
                <div>
                    <h3 style="margin-bottom: 0.25rem;">{{ $employee->name }}</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        CUIL: <strong style="color: var(--text-primary);">{{ $employee->currentCompanyProfile->cuil ?? 'N/A' }}</strong> | 
                        Email: <strong style="color: var(--text-primary);">{{ $employee->email }}</strong> |
                        Depto: <strong style="color: var(--text-primary);">{{ $employee->currentCompanyProfile->department ?? 'N/A' }}</strong>
                    </p>
                    @if ($employee->currentCompanyProfile)
                        <div style="margin-top: 0.5rem;">
                            @if ($employee->currentCompanyProfile->certificate_expires_at)
                                @if ($employee->currentCompanyProfile->certificate_expires_at->isPast())
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);"><i class="ri-error-warning-line"></i> Certificado Expirado</span>
                                @else
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i class="ri-shield-check-line"></i> Cert. Válido hasta: {{ $employee->currentCompanyProfile->certificate_expires_at->format('d/m/Y') }}</span>
                                @endif
                            @else
                                <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);"><i class="ri-loader-4-line" style="animation: spin 1s linear infinite;"></i> Generando Certificado...</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            
            <a href="{{ route('employees.download-zip', ['id' => $employeeId]) }}" class="btn px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition flex items-center gap-2 text-sm font-medium" style="background: rgba(37, 99, 235, 1); border-color: rgba(37, 99, 235, 1); box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.39);">
                <i class="ri-file-zip-line text-lg"></i> Descargar ZIP Masivo
            </a>
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
                            @if(in_array($payslip->status, ['signed_conforme', 'signed_no_conforme']) || $payslip->signature)
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success);">
                                    <i class="ri-check-shield-line"></i> Firmado
                                </span>
                            @else
                                <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: var(--warning);">
                                    <i class="ri-time-line"></i> Pendiente
                                </span>
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
