<?php

namespace App\Jobs;

use App\Models\EmployeeProfile;
use App\Models\Company;
use App\Services\EmployeeCertificateGenerator;
use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateEmployeeCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $employeeProfileId;
    public $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct($employeeProfileId)
    {
        $this->employeeProfileId = $employeeProfileId;
        // Obtenemos el tenant ID si estamos en contexto
        $this->tenantId = tenant('id');
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeeCertificateGenerator $certGenerator): void
    {
        if ($this->tenantId) {
            tenancy()->initialize($this->tenantId);
        }

        try {
            $profile = EmployeeProfile::withoutGlobalScope(CurrentCompanyScope::class)->findOrFail($this->employeeProfileId);
            $user = $profile->user;
            $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->findOrFail($profile->company_id);

            // Si hay un certificado anterior, lo eliminamos (Garbage Collection)
            if ($profile->certificate_path) {
                Storage::disk('local')->delete($profile->certificate_path);
            }

            // Generamos el certificado
            $certData = $certGenerator->generate(
                $user->name,
                $profile->cuil ?: ($profile->document_number ?: 'SIN_CUIL'),
                $company->name,
                $profile->id
            );

            // Actualizamos la base de datos
            $profile->update([
                'certificate_path'       => $certData['pfx_path'],
                'certificate_password'   => $certData['pfx_password'],
                'certificate_expires_at' => $certData['expires_at'],
            ]);

            Log::info("[GenerateEmployeeCertificate] Certificado generado para el empleado #{$profile->id}");
        } catch (Throwable $e) {
            Log::error("[GenerateEmployeeCertificate] Error al generar certificado para empleado #{$this->employeeProfileId}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
