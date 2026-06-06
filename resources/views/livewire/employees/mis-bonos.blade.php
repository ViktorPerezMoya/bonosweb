<div style="min-height: 100vh; padding: 1.5rem 1rem; max-width: 600px; margin: 0 auto;">

    {{-- ── Cabecera ──────────────────────────────────────────────────────────── --}}
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
        <div style="background: var(--accent); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="ri-user-line" style="font-size: 1.4rem; color: #fff;"></i>
        </div>
        <div>
            <div style="font-weight: 600; font-size: 1rem;">{{ Auth::user()->name }}</div>
            <div style="font-size: 0.82rem; color: var(--text-secondary);">Mis Recibos de Sueldo</div>
        </div>
        <div style="margin-left: auto;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        style="background: transparent; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; color: var(--text-secondary); font-size: 0.8rem; cursor: pointer;">
                    <i class="ri-logout-box-r-line"></i> Salir
                </button>
            </form>
        </div>
    </div>

    {{-- ── Lista de recibos ──────────────────────────────────────────────────── --}}
    @if ($payslips->isEmpty())
        <div class="glass-panel" style="padding: 3rem 1.5rem; text-align: center;">
            <i class="ri-file-list-3-line" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 1rem;"></i>
            <p style="color: var(--text-secondary);">Todavía no tenés recibos disponibles.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach ($payslips as $payslip)
                @php
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ];
                    $tipoLabels = [
                        'mensual'      => 'Mensual',
                        'sac'          => 'SAC',
                        'vacaciones'   => 'Vacaciones',
                        'gratificacion'=> 'Gratificación',
                        'final'        => 'Liquidación Final',
                        'retroactivo'  => 'Retroactivo',
                        'quincena'     => 'Quincena',
                        'anticipo'     => 'Anticipo',
                    ];
                    $mes       = $meses[$payslip->period_month] ?? $payslip->period_month;
                    $tipo      = $tipoLabels[$payslip->liquidation_type] ?? $payslip->liquidation_type;
                    $isSigned  = $payslip->status === 'signed';
                @endphp

                <div class="glass-panel"
                     style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;">

                    {{-- Icono según estado --}}
                    <div style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%;
                                background: {{ $isSigned ? 'rgba(16,185,129,0.15)' : 'rgba(251,191,36,0.15)' }};
                                display: flex; align-items: center; justify-content: center;">
                        <i class="{{ $isSigned ? 'ri-shield-check-line' : 'ri-time-line' }}"
                           style="font-size: 1.25rem; color: {{ $isSigned ? 'var(--success)' : 'var(--warning)' }};"></i>
                    </div>

                    {{-- Datos del recibo --}}
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $mes }} {{ $payslip->period_year }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            {{ $tipo }}
                            &nbsp;·&nbsp;
                            <span style="color: {{ $isSigned ? 'var(--success)' : 'var(--warning)' }}; font-weight: 500;">
                                {{ $isSigned ? 'Firmado' : 'Pendiente' }}
                            </span>
                        </div>
                    </div>

                    {{-- Botón ver --}}
                    <a href="{{ route('payslips.view', $payslip->id) }}"
                       target="_blank"
                       style="flex-shrink: 0; display: flex; align-items: center; justify-content: center;
                              width: 38px; height: 38px; border-radius: var(--radius-sm);
                              background: var(--surface-2); color: var(--accent); text-decoration: none;
                              transition: background 0.2s;"
                       onmouseover="this.style.background='var(--accent)';this.style.color='#fff'"
                       onmouseout="this.style.background='var(--surface-2)';this.style.color='var(--accent)'"
                       title="Ver recibo">
                        <i class="ri-eye-line" style="font-size: 1.1rem;"></i>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        @if ($payslips->hasPages())
            <div style="margin-top: 1.5rem;">
                {{ $payslips->links() }}
            </div>
        @endif
    @endif
</div>
