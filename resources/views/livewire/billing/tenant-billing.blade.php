<div>

    {{-- ── Alerta de servicio suspendido ── --}}
    @if($isSuspended)
    <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); border-radius: 12px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
        <i class="ri-error-warning-fill" style="font-size: 1.8rem; color: var(--danger);"></i>
        <div>
            <div style="font-weight: 700; color: #f87171; font-size: 1rem;">Servicio Suspendido</div>
            <div style="color: var(--text-secondary); font-size: 0.88rem; margin-top: 0.2rem;">
                El servicio ha sido suspendido por deuda vencida. Para reactivar su acceso, informe un pago o contáctese con BonosWeb.
            </div>
        </div>
    </div>
    @endif

    {{-- ── Flash message ── --}}
    @if(session()->has('message'))
    <div class="alert alert-success" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="ri-check-double-line" style="font-size: 1.2rem;"></i>
        {{ session('message') }}
    </div>
    @endif

    {{-- ── KPI Cards ── --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; margin-bottom: 2rem;">

        {{-- Saldo corriente --}}
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-scales-3-line"></i> Saldo Corriente
            </h3>
            <div style="font-size: 2rem; font-weight: 700; color: {{ $currentBalance > 0 ? 'var(--warning)' : 'var(--success)' }};">
                $ {{ number_format($currentBalance, 2, ',', '.') }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem;">
                {{ $currentBalance > 0 ? 'Deuda pendiente de pago' : 'Sin deuda pendiente ✓' }}
            </div>
        </div>

        {{-- Monto del servicio --}}
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-service-line"></i> Cuota Mensual
            </h3>
            <div style="font-size: 2rem; font-weight: 700; color: white;">
                $ {{ number_format($serviceAmount, 2, ',', '.') }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem;">Importe base del servicio</div>
        </div>

        {{-- Próximo vencimiento --}}
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-calendar-event-line"></i> Próximo Vencimiento
            </h3>
            @if($nextInvoice)
                <div style="font-size: 1.5rem; font-weight: 700; color: {{ $nextInvoice->status === 'overdue' ? 'var(--danger)' : 'var(--warning)' }};">
                    {{ \Carbon\Carbon::parse($nextInvoice->due_date)->format('d/m/Y') }}
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem;">
                    @if($nextInvoice->status === 'overdue')
                        <span style="color: var(--danger);">⚠ VENCIDA hace {{ \Carbon\Carbon::parse($nextInvoice->due_date)->diffForHumans() }}</span>
                    @else
                        Vence {{ \Carbon\Carbon::parse($nextInvoice->due_date)->diffForHumans() }}
                    @endif
                </div>
            @else
                <div style="font-size: 1.2rem; font-weight: 600; color: var(--success);">Sin vencimientos</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem;">No hay facturas pendientes</div>
            @endif
        </div>
    </div>

    {{-- ── Botón informar pago ── --}}
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="margin: 0 0 0.3rem 0; font-size: 1rem;">
                <i class="ri-bank-card-line" style="color: var(--accent);"></i> Informar un Pago
            </h3>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">
                Subí tu comprobante de transferencia o depósito. El equipo de BonosWeb lo verificará y aprobará el pago.
            </p>
        </div>
        <button wire:click="openPaymentModal" class="btn btn-primary" style="background: var(--accent); white-space: nowrap;">
            <i class="ri-upload-2-line"></i> Informar Pago
        </button>
    </div>

    {{-- ── Historial de Pagos ── --}}
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1.2rem 0; font-size: 1rem;">
            <i class="ri-receipt-line" style="color: var(--accent);"></i> Mis Pagos Informados
        </h3>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Comprobante</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td style="color: var(--text-secondary);">
                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                        </td>
                        <td>
                            <strong style="color: white;">$ {{ number_format($payment->amount, 2, ',', '.') }}</strong>
                        </td>
                        <td>
                            @if($payment->receipt_path)
                                <span style="color: var(--success); font-size: 0.85rem;">
                                    <i class="ri-file-check-line"></i> Adjuntado
                                </span>
                            @else
                                <span style="color: var(--text-secondary); font-size: 0.85rem;">Sin comprobante</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status === 'approved')
                                <span class="badge" style="background: rgba(16,185,129,0.2); color: var(--success);">
                                    <i class="ri-check-double-line"></i> Aprobado
                                </span>
                            @elseif($payment->status === 'rejected')
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: var(--danger);">
                                    <i class="ri-close-circle-line"></i> Rechazado
                                </span>
                            @else
                                <span class="badge" style="background: rgba(245,158,11,0.2); color: var(--warning);">
                                    <i class="ri-time-line"></i> En revisión
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            <i class="ri-inbox-line" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                            Aún no has informado ningún pago.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Historial de Facturas ── --}}
    <div class="glass-panel" style="padding: 1.5rem;">
        <h3 style="margin: 0 0 1.2rem 0; font-size: 1rem;">
            <i class="ri-file-list-3-line" style="color: var(--accent);"></i> Historial de Facturas
        </h3>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td style="color: var(--text-secondary);">
                            {{ ucfirst(\Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->locale('es')->isoFormat('MMMM [de] Y')) }}
                        </td>
                        <td>
                            <strong style="color: white;">$ {{ number_format($invoice->amount, 2, ',', '.') }}</strong>
                        </td>
                        <td>
                            <span style="color: {{ $invoice->status === 'overdue' ? 'var(--danger)' : 'var(--text-secondary)' }};">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                            </span>
                        </td>
                        <td>
                            @if($invoice->status === 'paid')
                                <span class="badge" style="background: rgba(16,185,129,0.2); color: var(--success);">Pagada</span>
                            @elseif($invoice->status === 'overdue')
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: var(--danger);">Vencida</span>
                            @else
                                <span class="badge" style="background: rgba(245,158,11,0.2); color: var(--warning);">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                            <i class="ri-file-line" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                            Sin facturas generadas aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- ════ MODAL: Informar Pago ════ --}}
    @if($showPaymentModal)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 460px; padding: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0;">
                <i class="ri-bank-card-line" style="color: var(--accent);"></i> Informar un Pago
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                Completá los datos del pago que realizaste. El equipo de BonosWeb verificará el comprobante y lo aprobará.
            </p>

            <form wire:submit.prevent="reportPayment">

                <div class="form-group">
                    <label class="form-label">Monto Pagado ($)</label>
                    <input type="number" step="0.01" min="0.01" wire:model="paymentAmount"
                           class="form-control" placeholder="Ej: 15000.00" required>
                    @error('paymentAmount') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Comprobante de Pago (Opcional)</label>
                    <div style="border: 2px dashed var(--glass-border); border-radius: 10px; padding: 1.2rem; text-align: center; cursor: pointer; transition: border-color 0.2s;"
                         onclick="document.getElementById('receipt-input').click()"
                         style="border-color: {{ $receipt ? 'var(--success)' : 'var(--glass-border)' }};">
                        <input type="file" id="receipt-input" wire:model="receipt"
                               accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                        @if($receipt)
                            <div style="color: var(--success);">
                                <i class="ri-file-check-line" style="font-size: 1.5rem;"></i>
                                <div style="font-size: 0.85rem; margin-top: 0.3rem;">{{ $receipt->getClientOriginalName() }}</div>
                            </div>
                        @else
                            <div style="color: var(--text-secondary);">
                                <i class="ri-upload-cloud-2-line" style="font-size: 1.5rem;"></i>
                                <div style="font-size: 0.85rem; margin-top: 0.3rem;">Clic para adjuntar PDF, JPG o PNG (máx. 5MB)</div>
                            </div>
                        @endif
                    </div>
                    @error('receipt') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="receipt" style="font-size: 0.8rem; color: var(--accent); margin-top: 0.3rem;">
                        <i class="ri-loader-4-line"></i> Subiendo archivo...
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" wire:click="$set('showPaymentModal', false)"
                            class="btn" style="background: rgba(255,255,255,0.1);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">
                        <span wire:loading.remove wire:target="reportPayment">
                            <i class="ri-send-plane-line"></i> Enviar Comprobante
                        </span>
                        <span wire:loading wire:target="reportPayment">
                            <i class="ri-loader-4-line"></i> Enviando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
