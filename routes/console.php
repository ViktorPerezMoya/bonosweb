<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendBatchNotificationsJob;
use App\Jobs\Billing\GenerateMonthlyInvoicesJob;
use App\Jobs\Billing\SuspendOverdueTenantsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Notificaciones de lotes de liquidaciones (cada minuto)
Schedule::job(new SendBatchNotificationsJob)->everyMinute();

// ─── Módulo de Facturación ──────────────────────────────────────────────────

// Genera facturas mensuales: se ejecuta todos los días a las 09:00 hs.
// El Job internamente verifica que sea después del día 15 antes de actuar.
Schedule::job(new GenerateMonthlyInvoicesJob)->dailyAt('09:00');

// Suspende tenants con deuda vencida hace +2 semanas: se ejecuta cada día a las 08:00 hs.
// Corre antes que la generación para no suspender el mismo día que se factura.
Schedule::job(new SuspendOverdueTenantsJob)->dailyAt('08:00');

// Renovación automática de certificados digitales para empleados a las 02:00 hs
Schedule::command('bonosweb:renew-employee-certs')->dailyAt('02:00');

// Respaldo completo de bases de datos a las 01:00 hs
Schedule::command('backup:databases')->dailyAt('01:00');
