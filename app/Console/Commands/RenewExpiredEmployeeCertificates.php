<?php

namespace App\Console\Commands;

use App\Models\EmployeeProfile;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewExpiredEmployeeCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonosweb:renew-employee-certs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca empleados con certificados vencidos y despacha Jobs para renovarlos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de renovación de certificados de empleados...');
        $count = 0;

        // Iteramos por todos los tenants
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            // Buscamos perfiles activos que ya tengan certificado y esté vencido, o que directamente no tengan certificado.
            // withoutGlobalScopes() para saltarnos el CurrentCompanyScope, ya que es un proceso en background global del tenant
            $expiredProfiles = EmployeeProfile::withoutGlobalScopes()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('certificate_expires_at')
                          ->orWhere('certificate_expires_at', '<', now());
                })
                ->get();

            foreach ($expiredProfiles as $profile) {
                \App\Jobs\GenerateEmployeeCertificate::dispatch($profile->id)->onQueue('default');
                $count++;
                $this->line("Despachado job de renovación para empleado ID: {$profile->id} en Tenant: {$tenant->id}");
            }

            tenancy()->end();
        }

        $this->info("Proceso finalizado. Se enviaron a la cola {$count} renovaciones de certificados.");
        Log::info("[CRON] bonosweb:renew-employee-certs ejecutado. {$count} certificados enviados a renovar.");
    }
}
