<div>

    {{-- ── KPI Cards ── --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; margin-bottom: 2rem;">
        <div class="glass-panel" style="padding: 1.4rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-money-dollar-circle-line"></i> Deuda Total
            </h3>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--warning);">
                $ {{ number_format($stats['total_balance'], 2, ',', '.') }}
            </div>
        </div>
        <div class="glass-panel" style="padding: 1.4rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-time-line"></i> Facturas Pendientes
            </h3>
            <div style="font-size: 1.8rem; font-weight: 700; color: {{ $stats['pending_invoices'] > 0 ? '#fbbf24' : 'white' }};">
                {{ $stats['pending_invoices'] }}
            </div>
        </div>
        <div class="glass-panel" style="padding: 1.4rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-error-warning-line"></i> Facturas Vencidas
            </h3>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--danger);">
                {{ $stats['overdue_invoices'] }}
            </div>
        </div>
        <div class="glass-panel" style="padding: 1.4rem;">
            <h3 style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">
                <i class="ri-forbid-line"></i> Suspendidas
            </h3>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--danger);">
                {{ $stats['suspended'] }}
            </div>
        </div>
    </div>

    {{-- ── Fila: Configuración Global + Pagos Pendientes ── --}}
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 2rem;">

        {{-- Config global --}}
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem;">
                <i class="ri-settings-3-line" style="color: var(--accent);"></i> Config. Global
            </h3>
            <div style="margin-bottom: 1rem;">
                <span style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Tasa de Inflación Mensual</span>
                <div style="font-size: 2rem; font-weight: 700; color: white; margin-top: 0.25rem;">
                    {{ $inflationRate }}%
                </div>
            </div>
            <button wire:click="openSettingsModal" class="btn btn-primary" style="background: var(--accent); font-size: 0.85rem; width: 100%;">
                <i class="ri-edit-line"></i> Editar Configuración
            </button>
        </div>

        {{-- Pagos pendientes de aprobación --}}
        <div class="glass-panel" style="padding: 1.5rem;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem;">
                <i class="ri-bank-card-line" style="color: var(--warning);"></i> Comprobantes Pendientes de Aprobación
            </h3>
            @forelse($pendingPayments as $payment)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(255,255,255,0.04); border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid var(--glass-border);">
                    <div>
                        <div style="font-weight: 600; color: white; font-size: 0.9rem;">{{ $payment->tenant->company_name ?? 'N/A' }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                            &nbsp;·&nbsp;
                            <strong style="color: #34d399;">$ {{ number_format($payment->amount, 2, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        @if($payment->receipt_path)
                            <a href="{{ route('superadmin.receipts', ['tenantId' => basename(dirname($payment->receipt_path)), 'filename' => basename($payment->receipt_path)]) }}" target="_blank" class="btn" style="background: rgba(255,255,255,0.08); color: var(--text-secondary); padding: 0.35rem 0.6rem; font-size: 0.78rem;" title="Ver comprobante">
                                <i class="ri-file-pdf-line"></i>
                            </a>
                        @endif
                        <button wire:click="approvePayment({{ $payment->id }})" class="btn" style="background: rgba(16,185,129,0.15); color: var(--success); padding: 0.35rem 0.7rem; font-size: 0.8rem;" title="Aprobar">
                            <i class="ri-check-line"></i> Aprobar
                        </button>
                        <button wire:click="rejectPayment({{ $payment->id }})" class="btn" style="background: rgba(239,68,68,0.12); color: var(--danger); padding: 0.35rem 0.7rem; font-size: 0.8rem;" title="Rechazar">
                            <i class="ri-close-line"></i> Rechazar
                        </button>
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: var(--text-secondary); padding: 1.5rem 0; font-size: 0.9rem;">
                    <i class="ri-check-double-line" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: var(--success);"></i>
                    Sin comprobantes pendientes.
                </p>
            @endforelse
        </div>
    </div>

    {{-- ── Tabla: Facturación por Tenant ── --}}
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 1rem;">
            <h3 style="margin: 0; font-size: 1rem;">
                <i class="ri-building-4-line" style="color: var(--accent);"></i> Estado de Cuenta por Empresa
            </h3>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <select wire:model.live="filterStatus" style="background: rgba(255,255,255,0.07); color: var(--text-primary); border: 1px solid var(--glass-border); border-radius: 8px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    <option value="">Todos</option>
                    <option value="active">Activas</option>
                    <option value="suspended">Suspendidas</option>
                </select>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Monto Servicio</th>
                        <th>Día de Pago</th>
                        <th>Inflación</th>
                        <th>Saldo Corriente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr style="opacity: {{ $tenant->is_suspended ? '0.65' : '1' }}">
                        <td>
                            <strong style="display: block;">{{ $tenant->company_name }}</strong>
                            <span style="color: var(--text-secondary); font-size: 0.8rem;">{{ $tenant->id }}</span>
                        </td>
                        <td>
                            <span style="color: white; font-weight: 600;">$ {{ number_format($tenant->service_base_amount ?? 0, 2, ',', '.') }}</span>
                        </td>
                        <td>
                            <span style="color: var(--text-secondary);">Día {{ $tenant->payment_day ?? 15 }}</span>
                        </td>
                        <td>
                            @if($tenant->apply_inflation)
                                <span class="badge" style="background: rgba(139,92,246,0.2); color: #a78bfa;">
                                    <i class="ri-percent-line"></i> Activa
                                </span>
                            @else
                                <span class="badge" style="background: rgba(255,255,255,0.07); color: var(--text-secondary);">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <span style="color: {{ ($tenant->current_balance ?? 0) > 0 ? 'var(--warning)' : 'var(--success)' }}; font-weight: 700; font-size: 1rem;">
                                $ {{ number_format($tenant->current_balance ?? 0, 2, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            @if($tenant->is_suspended)
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: var(--danger);">Suspendida</span>
                            @else
                                <span class="badge" style="background: rgba(16,185,129,0.2); color: var(--success);">Activa</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.4rem;">
                                <button wire:click="openBillingConfig('{{ $tenant->id }}')" class="btn" style="background: rgba(139,92,246,0.15); color: #a78bfa; padding: 0.35rem 0.6rem; font-size: 0.8rem;" title="Configurar Facturación">
                                    <i class="ri-settings-line"></i>
                                </button>
                                <button wire:click="openPaymentModal('{{ $tenant->id }}')" class="btn" style="background: rgba(16,185,129,0.15); color: var(--success); padding: 0.35rem 0.6rem; font-size: 0.8rem;" title="Registrar Pago Manual">
                                    <i class="ri-add-circle-line"></i>
                                </button>
                                @if(in_array($tenant->id, $tenantsWithActiveInvoice))
                                    <button class="btn" disabled style="background: rgba(255,255,255,0.05); color: var(--text-secondary); padding: 0.35rem 0.6rem; font-size: 0.8rem; cursor: not-allowed; opacity: 0.5;" title="Ya existe factura activa para este mes">
                                        <i class="ri-bill-line"></i> Facturado
                                    </button>
                                @else
                                    <button wire:click="issueInvoiceForTenant('{{ $tenant->id }}')"
                                        wire:confirm="¿Generar factura del mes actual para {{ $tenant->company_name }}?"
                                        class="btn"
                                        style="background: rgba(59,130,246,0.15); color: #60a5fa; padding: 0.35rem 0.6rem; font-size: 0.8rem;"
                                        title="Emitir factura del mes actual">
                                        <i class="ri-play-circle-line"></i> Facturar
                                    </button>
                                @endif
                                @if($tenant->is_suspended)
                                <button wire:click="reactivateTenant('{{ $tenant->id }}')" class="btn" style="background: rgba(16,185,129,0.15); color: var(--success); padding: 0.35rem 0.7rem; font-size: 0.8rem;" title="Reactivar Servicio">
                                    <i class="ri-play-circle-line"></i> Reactivar
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No hay empresas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Tabla: Historial de Facturas ── --}}
    <div class="glass-panel" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 1rem;">
            <h3 style="margin: 0; font-size: 1rem;">
                <i class="ri-file-list-3-line" style="color: var(--accent);"></i> Historial de Facturas
            </h3>
            <select wire:model.live="filterStatus" style="background: rgba(255,255,255,0.07); color: var(--text-primary); border: 1px solid var(--glass-border); border-radius: 8px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                <option value="">Todos los estados</option>
                <option value="pending">Pendiente</option>
                <option value="paid">Pagada</option>
                <option value="overdue">Vencida</option>
                <option value="cancelled">Cancelada</option>
            </select>
        </div>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentInvoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->tenant->company_name ?? 'N/A' }}</strong></td>
                        <td style="color: var(--text-secondary);">
                            {{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->locale('es')->isoFormat('MMMM Y') }}
                        </td>
                        <td style="font-weight: 700; color: white;">$ {{ number_format($invoice->amount, 2, ',', '.') }}</td>
                        <td>
                            <span style="color: {{ \Carbon\Carbon::parse($invoice->due_date)->isPast() && $invoice->status === 'pending' ? 'var(--danger)' : 'var(--text-secondary)' }};">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                            </span>
                        </td>
                        <td>
                            @if($invoice->status === 'paid')
                                <span class="badge" style="background: rgba(16,185,129,0.2); color: var(--success);">Pagada</span>
                            @elseif($invoice->status === 'overdue')
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: var(--danger);">Vencida</span>
                            @elseif($invoice->status === 'cancelled')
                                <span class="badge" style="background: rgba(156,163,175,0.2); color: #9ca3af;">Cancelada</span>
                            @else
                                <span class="badge" style="background: rgba(245,158,11,0.2); color: var(--warning);">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                                <button wire:click="cancelInvoice({{ $invoice->id }})"
                                    wire:confirm="¿Cancelar esta factura? Se reducirá el saldo del tenant."
                                    class="btn"
                                    style="background: rgba(239,68,68,0.12); color: var(--danger); padding: 0.35rem 0.6rem; font-size: 0.8rem;"
                                    title="Cancelar factura">
                                    <i class="ri-close-circle-line"></i> Cancelar
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Sin facturas generadas aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">{{ $recentInvoices->links() }}</div>
    </div>


    {{-- ════ MODALES ════ --}}

    {{-- Modal: Configuración Global --}}
    @if($showSettingsModal)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 420px; padding: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0;">
                <i class="ri-settings-3-line" style="color: var(--accent);"></i> Configuración Global
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                Este porcentaje se aplicará a todos los tenants que tengan la inflación habilitada, pasado el día 15 del mes.
            </p>
            <form wire:submit.prevent="saveGlobalSettings">
                <div class="form-group">
                    <label class="form-label">Tasa de Inflación Mensual (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="inflationRate" class="form-control" placeholder="Ej: 5.5" required>
                    @error('inflationRate') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" wire:click="$set('showSettingsModal', false)" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal: Configuración de Facturación del Tenant --}}
    @if($showBillingConfigModal)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 460px; padding: 2rem;">
            <h3 style="margin: 0 0 1.5rem 0;">
                <i class="ri-building-line" style="color: var(--accent);"></i> Configuración de Facturación
            </h3>
            <form wire:submit.prevent="saveBillingConfig">
                <div class="form-group">
                    <label class="form-label">Monto Base del Servicio ($)</label>
                    <input type="number" step="0.01" min="0" wire:model="billingServiceAmount" class="form-control" placeholder="Ej: 15000.00" required>
                    @error('billingServiceAmount') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Día Fijo de Pago (mínimo 15)</label>
                    <input type="number" min="15" max="31" wire:model="billingPaymentDay" class="form-control" placeholder="Ej: 20" required>
                    @error('billingPaymentDay') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                    <small style="color: var(--text-secondary); font-size: 0.78rem;">Si el día supera el último del mes, se usará el último día hábil del mes.</small>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.9rem 1rem; background: rgba(255,255,255,0.04); border-radius: 8px; border: 1px solid var(--glass-border);">
                        <input type="checkbox" wire:model="billingApplyInflation" style="width: 18px; height: 18px; accent-color: var(--accent);">
                        <div>
                            <div style="color: white; font-weight: 500;">Aplicar aumento por inflación</div>
                            <div style="color: var(--text-secondary); font-size: 0.8rem;">Actualiza el monto mensualmente con la tasa global.</div>
                        </div>
                    </label>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" wire:click="$set('showBillingConfigModal', false)" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--accent);">
                        <i class="ri-save-line"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal: Pago Manual --}}
    @if($showPaymentModal)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000;">
        <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0;">
                <i class="ri-add-circle-line" style="color: var(--success);"></i> Registrar Pago Manual
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                Los pagos registrados manualmente quedan aprobados de inmediato y se descuentan del saldo del cliente.
            </p>
            <form wire:submit.prevent="registerManualPayment">
                <div class="form-group">
                    <label class="form-label">Monto Recibido ($)</label>
                    <input type="number" step="0.01" min="0.01" wire:model="paymentAmount" class="form-control" placeholder="Ej: 15000.00" required>
                    @error('paymentAmount') <span style="color: var(--danger); font-size: 0.83rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="btn" style="background: rgba(255,255,255,0.1);">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--success);">
                        <i class="ri-check-line"></i> Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
