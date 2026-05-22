<?php

namespace App\Jobs\Billing;

use App\Mail\ServiceSuspendedNotification;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SuspendOverdueTenantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Suspende automáticamente los tenants con facturas vencidas hace más de 2 semanas
     * y cuyo saldo no esté cubierto por pagos aprobados.
     * Se ejecuta diariamente.
     */
    public function handle(): void
    {
        $cutoffDate = Carbon::now()->subWeeks(2);

        Log::info("SuspendOverdueTenantsJob: Buscando facturas pendientes anteriores al {$cutoffDate->toDateString()}...");

        // Buscar todas las facturas pendientes cuya fecha de vencimiento superó las 2 semanas
        $overdueInvoices = TenantInvoice::where('status', 'pending')
            ->where('due_date', '<=', $cutoffDate)
            ->with('tenant')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $tenant = $invoice->tenant;

            // Saltar si el tenant ya está suspendido o no existe
            if (!$tenant || $tenant->is_suspended) {
                // Marcar la factura como vencida de todas formas
                if ($tenant && $tenant->is_suspended) {
                    $invoice->status = 'overdue';
                    $invoice->save();
                }
                continue;
            }

            // Verificar si los pagos aprobados cubren el saldo total adeudado
            $totalApprovedPayments = TenantPayment::where('tenant_id', $tenant->id)
                ->where('status', 'approved')
                ->sum('amount');

            $totalOwed = TenantInvoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('amount');

            if ((float) $totalApprovedPayments >= (float) $totalOwed) {
                // El saldo está cubierto: marcar como pagado
                $invoice->status = 'paid';
                $invoice->save();

                Log::info("SuspendOverdueTenantsJob: Tenant [{$tenant->id}] – Factura {$invoice->id} cubierta por pagos. Marcada como pagada.");
                continue;
            }

            // El saldo no está cubierto: suspender el servicio
            $tenant->is_suspended = true;
            $tenant->save();

            $invoice->status = 'overdue';
            $invoice->save();

            Log::warning("SuspendOverdueTenantsJob: Tenant [{$tenant->id}] SUSPENDIDO. Saldo adeudado: {$totalOwed} – Pagos aprobados: {$totalApprovedPayments}");

            // Notificar la suspensión al administrador del tenant
            $adminEmail = $tenant->admin_email ?? null;
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new ServiceSuspendedNotification($tenant, $invoice));
                } catch (\Exception $e) {
                    Log::error("SuspendOverdueTenantsJob: Error enviando email de suspensión a tenant [{$tenant->id}]: " . $e->getMessage());
                }
            }
        }

        Log::info("SuspendOverdueTenantsJob: Proceso completado. Facturas evaluadas: {$overdueInvoices->count()}");
    }
}
