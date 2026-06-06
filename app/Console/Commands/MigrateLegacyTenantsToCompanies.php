<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Scopes\CurrentCompanyScope;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Data-backfill: inicializa la empresa principal (is_main = true) dentro de
 * cada tenant existente, migrando los datos corporativos heredados que antes
 * vivían en la tabla central "tenants", y repara todas las claves foráneas
 * (company_id) en employee_profiles, upload_batches y payslips.
 *
 * Es seguro ejecutarlo múltiples veces (idempotente): si un tenant ya tiene
 * una empresa principal, se omite sin tocar sus datos.
 */
class MigrateLegacyTenantsToCompanies extends Command
{
    protected $signature = 'tenants:migrate-legacy-companies
                            {--dry-run : Simula la migración sin escribir nada en la base de datos}';

    protected $description = 'Inicializa la empresa principal (is_main=true) de cada tenant existente '
                           . 'y repara los company_id nulos en employee_profiles, upload_batches y payslips.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠  Modo DRY-RUN activo — no se escribirá nada en la base de datos.');
        }

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No hay tenants registrados. Nada que migrar.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$tenants->count()} tenant(s)...");
        $this->newLine();

        $ok      = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($tenants as $tenant) {
            $label = "[{$tenant->id}] {$tenant->company_name}";

            try {
                $tenant->run(function () use ($tenant, $label, $dryRun, &$ok, &$skipped) {

                    // ── Idempotencia ─────────────────────────────────────────
                    $alreadyHasMain = Company::withoutGlobalScope(CurrentCompanyScope::class)
                        ->where('is_main', true)
                        ->exists();

                    if ($alreadyHasMain) {
                        $this->line("  <fg=yellow>SKIP</> {$label} — ya tiene empresa principal.");
                        $skipped++;
                        return;
                    }

                    // ── Datos a migrar desde el Tenant central ────────────────
                    // cuit es NOT NULL + UNIQUE → si el tenant no lo tiene,
                    // usamos un placeholder "PENDIENTE-<id>" para no romper la
                    // restricción. El admin puede actualizarlo luego.
                    $cuit = $tenant->employer_cuit ?? ('PENDIENTE-' . $tenant->id);

                    $companyData = [
                        'name'    => $tenant->company_name ?? $tenant->id,
                        'cuit'    => $cuit,
                        'is_main' => true,

                        // Coordenadas de la firma visual (se trasladan tal cual)
                        'signature_x' => $tenant->signature_x ?? null,
                        'signature_y' => $tenant->signature_y ?? null,
                        'signature_w' => $tenant->signature_w ?? null,
                        'signature_h' => $tenant->signature_h ?? null,

                        // El certificado heredado era formato PEM (cert_path / cert_key_path),
                        // incompatible con el nuevo campo PKCS#12 (signature_pfx_path).
                        // Se deja null para que el SuperAdmin regenere el PFX desde el panel.
                        'signature_pfx_path'     => null,
                        'signature_pfx_password' => null,
                    ];

                    if ($dryRun) {
                        $this->line("  <fg=cyan>DRY-RUN</> {$label}");
                        $this->line("    company data: " . json_encode($companyData, JSON_PRETTY_PRINT));
                        if (str_starts_with($companyData['cuit'], 'PENDIENTE-')) {
                            $this->warn("    [!] employer_cuit no configurado → se usará placeholder '{$companyData['cuit']}'");
                        }
                        return;
                    }

                    // ── Transacción por tenant ────────────────────────────────
                    DB::beginTransaction();

                    try {
                        // 1. Crear empresa principal
                        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
                            ->create($companyData);

                        // 2. Reparar claves foráneas — employee_profiles
                        $updatedProfiles = DB::table('employee_profiles')
                            ->whereNull('company_id')
                            ->update(['company_id' => $company->id]);

                        // 3. Reparar claves foráneas — upload_batches
                        $updatedBatches = DB::table('upload_batches')
                            ->whereNull('company_id')
                            ->update(['company_id' => $company->id]);

                        // 4. Reparar claves foráneas — payslips
                        $updatedPayslips = DB::table('payslips')
                            ->whereNull('company_id')
                            ->update(['company_id' => $company->id]);

                        DB::commit();

                        $certWarning = ($tenant->cert_path || $tenant->cert_key_path)
                            ? ' <fg=yellow>[!] Cert PEM no migrado → regenerar PFX en panel</>'
                            : '';

                        $cuitWarning = str_starts_with($company->cuit, 'PENDIENTE-')
                            ? ' <fg=yellow>[!] CUIT pendiente → actualizar en panel</>'
                            : '';

                        $this->line(
                            "  <fg=green>OK</>   {$label} — "
                            . "company_id={$company->id} | "
                            . "profiles={$updatedProfiles} | "
                            . "batches={$updatedBatches} | "
                            . "payslips={$updatedPayslips}"
                            . $certWarning
                            . $cuitWarning
                        );

                        $ok++;

                    } catch (\Throwable $inner) {
                        DB::rollBack();
                        throw $inner; // re-lanza para el catch externo
                    }
                });

            } catch (\Throwable $e) {
                $this->error("  FAIL  {$label} — {$e->getMessage()}");
                $failed++;
            }
        }

        // ── Resumen final ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('── Resumen ──────────────────────────────────────────');
        $this->info("  Migrados exitosamente : {$ok}");
        $this->info("  Omitidos (ya migrados) : {$skipped}");
        $failed > 0
            ? $this->error("  Fallidos               : {$failed}")
            : $this->info("  Fallidos               : {$failed}");
        $this->info('─────────────────────────────────────────────────────');

        if ($failed > 0) {
            $this->warn('Revisá los errores arriba. Los tenants fallidos NO fueron modificados (rollback aplicado).');
        }

        if (!$dryRun && $ok > 0) {
            $this->newLine();
            $this->warn(
                'RECORDATORIO: Los tenants migrados que tenían cert_path/cert_key_path (PEM) '
                . 'necesitan un nuevo certificado PFX. Generalo desde el panel SuperAdmin → '
                . 'Gestionar Firmas → botón "Generar" por cada subempresa.'
            );
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
