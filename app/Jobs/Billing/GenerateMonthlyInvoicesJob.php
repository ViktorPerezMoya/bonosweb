<?php

namespace App\Jobs\Billing;

use App\Mail\MonthlyInvoiceNotification;
use App\Models\GlobalSetting;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Genera las facturas mensuales para todos los tenants activos.
     * Se ejecuta a partir del día 16 de cada mes.
     * - Aplica inflación a los tenants que lo tengan habilitado.
     * - Calcula la fecha de vencimiento respetando fines de semana.
     * - Actualiza el saldo corriente del tenant.
     * - Envía notificación por email al administrador del tenant.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        // Solo ejecutar después del día 15 del mes
        if ($now->day <= 15) {
            Log::info('GenerateMonthlyInvoicesJob: Ejecutado antes del día 16, omitiendo.');
            return;
        }

        $month = $now->month;
        $year  = $now->year;

        // Obtener tasa de inflación global (porcentaje)
        $inflationRate = (float) (GlobalSetting::where('key', 'inflation_rate')->value('value') ?? 0);

        // Procesar solo tenants activos (no suspendidos)
        $tenants = Tenant::where('is_suspended', false)->get();

        foreach ($tenants as $tenant) {
            // Evitar duplicar la factura del mismo mes/año
            $alreadyInvoiced = TenantInvoice::where('tenant_id', $tenant->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->exists();

            if ($alreadyInvoiced) {
                continue;
            }

            // Calcular monto: aplicar inflación si corresponde
            $amount = (float) $tenant->service_base_amount;

            if ($tenant->apply_inflation && $inflationRate > 0) {
                $amount = round($amount * (1 + ($inflationRate / 100)), 2);
                // Persistir el nuevo monto base con inflación aplicada
                $tenant->service_base_amount = $amount;
                $tenant->save();

                Log::info("GenerateMonthlyInvoicesJob: Inflación aplicada a tenant [{$tenant->id}]. Nuevo monto: {$amount}");
            }

            // Calcular fecha de vencimiento
            $dueDate = $this->calculateDueDate((int) $tenant->payment_day, $now);

            // Crear la factura mensual
            $invoice = TenantInvoice::create([
                'tenant_id'    => $tenant->id,
                'period_month' => $month,
                'period_year'  => $year,
                'amount'       => $amount,
                'due_date'     => $dueDate,
                'status'       => 'pending',
            ]);

            // Actualizar saldo corriente acumulativo
            $tenant->current_balance = round((float) $tenant->current_balance + $amount, 2);
            $tenant->save();

            Log::info("GenerateMonthlyInvoicesJob: Factura generada para tenant [{$tenant->id}] – Monto: {$amount} – Vencimiento: {$dueDate->toDateString()}");

            // Enviar notificación por email al administrador del tenant
            $adminEmail = $tenant->admin_email ?? null;
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new MonthlyInvoiceNotification($tenant, $invoice));
                } catch (\Exception $e) {
                    Log::error("GenerateMonthlyInvoicesJob: Error enviando email a tenant [{$tenant->id}]: " . $e->getMessage());
                }
            }
        }

        Log::info("GenerateMonthlyInvoicesJob: Proceso completado para {$month}/{$year}.");
    }

    /**
     * Calcula la fecha de vencimiento según las reglas de negocio:
     * - Mínimo el día 15 del mes.
     * - Si supera el último día del mes, usa el último día hábil del mes.
     * - Si la fecha ya pasó (el job corrió después del día de pago), se traslada al mismo día del mes siguiente.
     * - Si cae sábado o domingo, se adelanta al lunes siguiente.
     */
    private function calculateDueDate(int $paymentDay, Carbon $now): Carbon
    {
        // Regla: mínimo día 15
        $paymentDay = max(15, $paymentDay);

        // Calcular el vencimiento en el mes actual
        $lastDayOfMonth = $now->copy()->endOfMonth()->day;
        $effectiveDay   = min($paymentDay, $lastDayOfMonth);
        $dueDate        = $now->copy()->setDay($effectiveDay)->startOfDay();

        // Regla: si la fecha ya pasó, el vencimiento es el día de emisión (hoy)
        if ($dueDate->lte($now->copy()->startOfDay())) {
            $dueDate = $now->copy()->startOfDay();
        }

        // Regla: si cae en fin de semana, pasar al siguiente lunes
        if ($dueDate->isSaturday()) {
            $dueDate->addDays(2);
        } elseif ($dueDate->isSunday()) {
            $dueDate->addDay();
        }

        return $dueDate;
    }
}
