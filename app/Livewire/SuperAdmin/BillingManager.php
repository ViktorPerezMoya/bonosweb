<?php

namespace App\Livewire\SuperAdmin;

use App\Mail\MonthlyInvoiceNotification;
use App\Models\GlobalSetting;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class BillingManager extends Component
{
    use WithPagination;

    // Configuración global
    public float $inflationRate = 0;
    public bool $showSettingsModal = false;

    // Pago manual
    public bool $showPaymentModal = false;
    public ?string $paymentTenantId = null;
    public float $paymentAmount = 0;
    public string $paymentNotes = '';

    // Configuración individual del tenant (billing)
    public bool $showBillingConfigModal = false;
    public ?string $billingTenantId = null;
    public float $billingServiceAmount = 0;
    public int $billingPaymentDay = 15;
    public bool $billingApplyInflation = false;

    // Filtros de tabla
    public string $filterStatus = '';
    public string $filterTenantId = '';

    public function mount(): void
    {
        $this->inflationRate = (float) (GlobalSetting::where('key', 'inflation_rate')->value('value') ?? 0);
    }

    // ─── Configuración Global ────────────────────────────────────────────────

    public function openSettingsModal(): void
    {
        $this->inflationRate = (float) (GlobalSetting::where('key', 'inflation_rate')->value('value') ?? 0);
        $this->showSettingsModal = true;
    }

    public function saveGlobalSettings(): void
    {
        $this->validate([
            'inflationRate' => 'required|numeric|min:0|max:100',
        ], [
            'inflationRate.required' => 'El porcentaje de inflación es obligatorio.',
            'inflationRate.numeric'  => 'Debe ser un número.',
            'inflationRate.min'      => 'No puede ser negativo.',
            'inflationRate.max'      => 'No puede superar el 100%.',
        ]);

        GlobalSetting::updateOrCreate(
            ['key' => 'inflation_rate'],
            ['value' => $this->inflationRate]
        );

        $this->showSettingsModal = false;
        session()->flash('message', "Tasa de inflación actualizada al {$this->inflationRate}%.");
    }

    // ─── Configuración de Facturación por Tenant ─────────────────────────────

    public function openBillingConfig(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->billingTenantId      = $tenant->id;
        $this->billingServiceAmount = (float) ($tenant->service_base_amount ?? 0);
        $this->billingPaymentDay    = (int) ($tenant->payment_day ?? 15);
        $this->billingApplyInflation = (bool) ($tenant->apply_inflation ?? false);
        $this->showBillingConfigModal = true;
    }

    public function saveBillingConfig(): void
    {
        $this->validate([
            'billingServiceAmount' => 'required|numeric|min:0',
            'billingPaymentDay'    => 'required|integer|min:15|max:31',
        ], [
            'billingPaymentDay.min' => 'El día de pago no puede ser inferior al 15.',
            'billingPaymentDay.max' => 'El día de pago no puede superar el 31.',
        ]);

        $tenant = Tenant::findOrFail($this->billingTenantId);
        $tenant->service_base_amount = $this->billingServiceAmount;
        $tenant->payment_day         = $this->billingPaymentDay;
        $tenant->apply_inflation     = $this->billingApplyInflation;
        $tenant->save();

        $this->showBillingConfigModal = false;
        session()->flash('message', "Configuración de facturación actualizada para {$tenant->company_name}.");
    }

    // ─── Gestión de Pagos ────────────────────────────────────────────────────

    public function openPaymentModal(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->paymentTenantId = $tenant->id;
        $this->paymentAmount   = 0;
        $this->paymentNotes    = '';
        $this->showPaymentModal = true;
    }

    public function registerManualPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
        ], [
            'paymentAmount.min' => 'El monto debe ser mayor a cero.',
        ]);

        $payment = TenantPayment::create([
            'tenant_id'    => $this->paymentTenantId,
            'amount'       => $this->paymentAmount,
            'payment_date' => Carbon::today(),
            'status'       => 'approved',  // Pago manual del SuperAdmin: aprobado directo
            'receipt_path' => null,
        ]);

        // Descontar del saldo corriente
        $tenant = Tenant::findOrFail($this->paymentTenantId);
        $tenant->current_balance = max(0, (float) $tenant->current_balance - $this->paymentAmount);
        $tenant->save();

        $this->markInvoicesAsPaid($this->paymentTenantId, (float) $this->paymentAmount);

        $this->showPaymentModal = false;
        session()->flash('message', "Pago de $ {$this->paymentAmount} registrado para {$tenant->company_name}.");
    }

    public function approvePayment(int $paymentId): void
    {
        $payment = TenantPayment::findOrFail($paymentId);
        if ($payment->status === 'pending_approval') {
            $payment->status = 'approved';
            $payment->save();

            // Descontar del saldo corriente del tenant
            $tenant = Tenant::findOrFail($payment->tenant_id);
            $tenant->current_balance = max(0, (float) $tenant->current_balance - (float) $payment->amount);
            $tenant->save();

            $this->markInvoicesAsPaid($payment->tenant_id, (float) $payment->amount);

            session()->flash('message', 'Pago aprobado correctamente.');
        }
    }

    public function rejectPayment(int $paymentId): void
    {
        $payment = TenantPayment::findOrFail($paymentId);
        $payment->status = 'rejected';
        $payment->save();

        session()->flash('message', 'Pago rechazado.');
    }

    public function reactivateTenant(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->is_suspended = false;
        $tenant->save();

        session()->flash('message', "{$tenant->company_name} ha sido reactivada.");
    }

    // ─── Cancelar Factura ────────────────────────────────────────────────────

    public function cancelInvoice(int $invoiceId): void
    {
        $invoice = TenantInvoice::findOrFail($invoiceId);

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            session()->flash('error', 'Esta factura no puede ser cancelada.');
            return;
        }

        $invoice->status = 'cancelled';
        $invoice->save();

        $tenant = Tenant::findOrFail($invoice->tenant_id);
        $tenant->current_balance = max(0, round((float) $tenant->current_balance - (float) $invoice->amount, 2));
        $tenant->save();

        session()->flash('message', 'Factura cancelada y saldo del tenant actualizado.');
    }

    // ─── Emitir Factura Manual para Tenant ───────────────────────────────────

    public function issueInvoiceForTenant(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $now    = Carbon::now();
        $month  = $now->month;
        $year   = $now->year;

        $exists = TenantInvoice::where('tenant_id', $tenantId)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('status', ['pending', 'paid'])
            ->exists();

        if ($exists) {
            session()->flash('error', "Ya existe una factura activa para {$tenant->company_name} en este mes.");
            return;
        }

        $inflationRate = (float) (GlobalSetting::where('key', 'inflation_rate')->value('value') ?? 0);
        $amount        = (float) $tenant->service_base_amount;

        if ($tenant->apply_inflation && $inflationRate > 0) {
            $amount                      = round($amount * (1 + ($inflationRate / 100)), 2);
            $tenant->service_base_amount = $amount;
            $tenant->save();
        }

        $dueDate = $this->calculateDueDate((int) $tenant->payment_day, $now);

        $invoice = TenantInvoice::create([
            'tenant_id'    => $tenantId,
            'period_month' => $month,
            'period_year'  => $year,
            'amount'       => $amount,
            'due_date'     => $dueDate,
            'status'       => 'pending',
        ]);

        $tenant->current_balance = round((float) $tenant->current_balance + $amount, 2);
        $tenant->save();

        $adminEmail = $tenant->admin_email ?? null;
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new MonthlyInvoiceNotification($tenant, $invoice));
            } catch (\Exception $e) {
                Log::error("issueInvoiceForTenant: Error enviando email a tenant [{$tenant->id}]: " . $e->getMessage());
            }
        }

        session()->flash('message', "Factura de \$ {$amount} generada para {$tenant->company_name}.");
    }

    private function markInvoicesAsPaid(string $tenantId, float $paidAmount): void
    {
        $invoices = TenantInvoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        foreach ($invoices as $invoice) {
            if ($paidAmount <= 0) {
                break;
            }

            if ($paidAmount >= (float) $invoice->amount) {
                $paidAmount -= (float) $invoice->amount;
                $invoice->status = 'paid';
                $invoice->save();
            } else {
                // Pago parcial: no alcanza para cubrir esta factura, se detiene
                break;
            }
        }
    }

    private function calculateDueDate(int $paymentDay, Carbon $now): Carbon
    {
        $paymentDay     = max(15, $paymentDay);
        $lastDayOfMonth = $now->copy()->endOfMonth()->day;
        $effectiveDay   = min($paymentDay, $lastDayOfMonth);
        $dueDate        = $now->copy()->setDay($effectiveDay)->startOfDay();

        if ($dueDate->lte($now->copy()->startOfDay())) {
            $dueDate = $now->copy()->startOfDay();
        }

        if ($dueDate->isSaturday()) {
            $dueDate->addDays(2);
        } elseif ($dueDate->isSunday()) {
            $dueDate->addDay();
        }

        return $dueDate;
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render()
    {
        $tenantsQuery = Tenant::query();
        if ($this->filterStatus === 'suspended') {
            $tenantsQuery->where('is_suspended', true);
        } elseif ($this->filterStatus === 'active') {
            $tenantsQuery->where('is_suspended', false);
        }

        $pendingPayments = TenantPayment::where('status', 'pending_approval')
            ->with('tenant')
            ->latest()
            ->get();

        $recentInvoices = TenantInvoice::with('tenant')
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest('due_date')
            ->paginate(10);

        $now = Carbon::now();
        $tenantsWithActiveInvoice = TenantInvoice::whereIn('status', ['pending', 'paid'])
            ->where('period_month', $now->month)
            ->where('period_year', $now->year)
            ->pluck('tenant_id')
            ->toArray();

        $stats = [
            'total_balance'    => Tenant::sum('current_balance'),
            'pending_invoices' => TenantInvoice::where('status', 'pending')->count(),
            'overdue_invoices' => TenantInvoice::where('status', 'overdue')->count(),
            'suspended'        => Tenant::where('is_suspended', true)->count(),
        ];

        return view('livewire.superadmin.billing-manager', [
            'tenants'                  => $tenantsQuery->get(),
            'pendingPayments'          => $pendingPayments,
            'recentInvoices'           => $recentInvoices,
            'stats'                    => $stats,
            'inflationRate'            => GlobalSetting::where('key', 'inflation_rate')->value('value') ?? 0,
            'tenantsWithActiveInvoice' => $tenantsWithActiveInvoice,
        ])->layout('components.layouts.superadmin', [
            'header' => 'Gestión de Cobros y Facturación',
            'title'  => 'Cobros – BonosWeb Central',
        ]);
    }
}
