<?php

namespace App\Jobs\Tenancy;

use App\Models\Company;
use App\Models\Scopes\CurrentCompanyScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Provisionamiento de datos iniciales para un Tenant recién creado.
 *
 * Posición en el pipeline (TenancyServiceProvider):
 *   CreateDatabase → MigrateDatabase → CreateTenantAdminUser → [ESTE JOB]
 *
 * Qué hace:
 *  1. Crea la Company principal (is_main = true) en la BD del tenant usando
 *     los datos que el SuperAdmin ingresó (company_name, employer_cuit).
 *  2. Vincula el usuario administrador ya existente con esa Company
 *     creando su EmployeeProfile inicial (si la columna cuil lo permite).
 *
 * Por qué aquí y no antes:
 *  - Debe correr después de MigrateDatabase (necesita la tabla `companies`).
 *  - Debe correr después de CreateTenantAdminUser (necesita el User en la BD).
 */
class ProvisionNewTenantData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Tenant $tenant;

    /**
     * stancl/tenancy JobPipeline pasa el modelo Tenant directamente,
     * no el evento, porque el .send() del pipeline devuelve $event->tenant.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        $this->tenant->run(function () {
            DB::beginTransaction();

            try {
                // ── 1. Empresa principal ──────────────────────────────────────
                // Idempotente: si ya existe (p. ej. por re-ejecución), no crea otra.
                $alreadyExists = Company::withoutGlobalScope(CurrentCompanyScope::class)
                    ->where('is_main', true)
                    ->exists();

                if ($alreadyExists) {
                    DB::commit();
                    return;
                }

                // Los campos company_name y employer_cuit son columnas custom del
                // Tenant central (getCustomColumns). Los campos admin_* viven en el
                // JSON 'data' del Tenant pero son accesibles vía magic __get.
                $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
                    ->create([
                        'name'                   => $this->tenant->company_name ?? $this->tenant->id,
                        'cuit'                   => $this->tenant->employer_cuit
                                                    ?? ('PENDIENTE-' . $this->tenant->id),
                        'is_main'                => true,
                        'signature_pfx_path'     => null,
                        'signature_pfx_password' => null,
                    ]);

                // ── Generar certificado digital automático ───────────────────
                $certGenerator = app(\App\Services\CompanyCertificateGenerator::class);
                $certData = $certGenerator->generate($company->name, $company->cuit, $company->id);

                $company->update([
                    'signature_pfx_path'       => $certData['pfx_path'],
                    'signature_pfx_password'   => $certData['pfx_password'],
                    'signature_pfx_expires_at' => $certData['expires_at'],
                ]);

                // Nota: NO se crea EmployeeProfile para el admin inicial.
                // Un usuario Admin/RRHH no requiere legajo. Si también actúa como
                // empleado, el legajo se crea manualmente desde el panel de RRHH.

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[ProvisionNewTenantData] Falló el provisionamiento del tenant ' . $this->tenant->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-lanza para que el pipeline marque el job como fallido
            }
        });
    }
}
