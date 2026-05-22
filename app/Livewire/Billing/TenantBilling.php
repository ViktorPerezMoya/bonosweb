<?php

namespace App\Livewire\Billing;

use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class TenantBilling extends Component
{
    use WithFileUploads;

    public bool $showPaymentModal = false;
    public float $paymentAmount   = 0;
    public $receipt                = null;

    public function openPaymentModal(): void
    {
        $this->paymentAmount = 0;
        $this->receipt       = null;
        $this->resetErrorBag();
        $this->showPaymentModal = true;
    }

    public function reportPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'receipt'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'paymentAmount.required' => 'Ingrese el monto pagado.',
            'paymentAmount.min'      => 'El monto debe ser mayor a cero.',
            'receipt.mimes'          => 'El comprobante debe ser PDF, JPG o PNG.',
            'receipt.max'            => 'El archivo no puede superar los 5 MB.',
        ]);

        $tenantId = tenant('id');
        $receiptPath = null;

        // Guardar el comprobante en storage local, organizado por tenant
        if ($this->receipt) {
            $receiptPath = $this->receipt->storeAs(
                "receipts/{$tenantId}",
                now()->format('YmdHis') . '_' . $this->receipt->getClientOriginalName(),
                'local'
            );
        }

        TenantPayment::create([
            'tenant_id'          => $tenantId,
            'amount'             => $this->paymentAmount,
            'receipt_path'       => $receiptPath,
            'payment_date'       => Carbon::today(),
            'status'             => 'pending_approval',
            'reported_by_user_id' => auth()->id(),
        ]);

        $this->showPaymentModal = false;
        $this->paymentAmount   = 0;
        $this->receipt         = null;

        session()->flash('message', 'Pago informado correctamente. El equipo de BonosWeb verificará y aprobará el comprobante a la brevedad.');
    }

    public function render()
    {
        $tenantId = tenant('id');
        $tenant   = tenancy()->tenant;

        $invoices = TenantInvoice::where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->latest('due_date')
            ->get();

        $payments = TenantPayment::where('tenant_id', $tenantId)
            ->latest()
            ->get();

        $nextInvoice = $invoices->whereIn('status', ['pending', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return view('livewire.billing.tenant-billing', [
            'currentBalance' => (float) ($tenant->current_balance ?? 0),
            'serviceAmount'  => (float) ($tenant->service_base_amount ?? 0),
            'isSuspended'    => (bool)  ($tenant->is_suspended ?? false),
            'nextInvoice'    => $nextInvoice,
            'invoices'       => $invoices,
            'payments'       => $payments,
        ])->layout('components.layouts.app', [
            'header' => 'Mi Cuenta y Pagos',
            'title'  => 'Facturación – BonosWeb',
        ]);
    }
}
