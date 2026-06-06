<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Scopes\CurrentCompanyScope;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantsManager extends Component
{
    use WithPagination;

    public $showModal = false;

    // Create form
    public $company_name    = '';
    public $employer_cuit   = '';
    public $subdomain       = '';
    public $admin_name      = '';
    public $admin_email     = '';
    public $admin_password  = '';

    // Edit modal
    public $showEditModal    = false;
    public $editTenantId     = null;
    public $editCompanyName  = '';
    public $editEmployerCuit = '';
    public $editAdminName    = '';
    public $editAdminEmail   = '';
    public $editAdminPassword = '';

    // Modal de gestión de certificados por subempresa
    public bool $showCompanyCertModal = false;
    public ?string $certTenantId      = null;
    public array $tenantCompanies     = [];
    // Modal de borrado total (Hard Delete)
    public bool    $showDeleteModal          = false;
    public ?string $deleteTenantId           = null;
    public ?string $deleteTenantName         = null;
    public string  $deleteConfirmationInput  = '';

    // Búsqueda en tiempo real sobre company_name
    public string  $search                   = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->company_name   = '';
        $this->employer_cuit  = '';
        $this->subdomain      = '';
        $this->admin_name     = '';
        $this->admin_email    = '';
        $this->admin_password = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function createTenant()
    {
        $this->validate([
            'company_name'   => 'required|string|max:255',
            'employer_cuit'  => ['nullable', 'regex:/^(20|23|24|27|30|33|34)\d{8}\d$/'],
            // unique:tenants,id verifica la PK de la tabla central (evita UniqueConstraintViolationException)
            // unique:domains,domain verifica que el dominio completo tampoco exista
            'subdomain'      => 'required|alpha_dash|unique:tenants,id|unique:domains,domain',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255',
            'admin_password' => 'required|min:6',
        ], [
            'employer_cuit.regex'   => 'El CUIT debe tener 11 dígitos numéricos válidos (sin guiones).',
            'subdomain.unique'      => 'Ese subdominio ya está en uso. Elegí otro.',
        ]);

        // Asegurar que el subdominio termine con bonosweb.com o localhost para dev
        $baseDomain = env('APP_ENV') === 'local' ? 'localhost' : 'bonosweb.com';
        $fullDomain = $this->subdomain . '.' . $baseDomain;

        // Crear el tenant. Esto dispara la creación de la DB automáticamente gracias a stancl/tenancy
        $tenant = Tenant::create([
            'id'             => $this->subdomain,
            'company_name'   => $this->company_name,
            'employer_cuit'  => preg_replace('/\D/', '', $this->employer_cuit) ?: null,
            'admin_name'     => $this->admin_name,
            'admin_email'    => $this->admin_email,
            'admin_password' => bcrypt($this->admin_password),
            'is_suspended'   => false,
        ]);

        // Registrar el dominio
        $tenant->domains()->create([
            'domain' => $fullDomain
        ]);

        session()->flash('message', 'Empresa registrada. Base de datos creada exitosamente.');
        $this->closeModal();
    }

    public function toggleSuspension($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->is_suspended = !$tenant->is_suspended;
        $tenant->save();

        $status = $tenant->is_suspended ? 'suspendida' : 'activada';
        session()->flash('message', "La empresa {$tenant->company_name} ha sido {$status}.");
    }


    public function generateCertificate($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->buildAndStoreCertificate($tenant);
        session()->flash('message', "Certificado criptográfico generado para {$tenant->company_name}.");
    }

    public function regenerateCertificate($id)
    {
        $tenant = Tenant::findOrFail($id);

        // Eliminar archivos previos del disco central (no afecta el contexto tenant)
        if ($tenant->cert_path && Storage::disk('local')->exists($tenant->cert_path)) {
            Storage::disk('local')->delete($tenant->cert_path);
        }
        if ($tenant->cert_key_path && Storage::disk('local')->exists($tenant->cert_key_path)) {
            Storage::disk('local')->delete($tenant->cert_key_path);
        }

        $this->buildAndStoreCertificate($tenant);
        session()->flash('message', "Certificado de {$tenant->company_name} renovado exitosamente. Válido por 10 años.");
    }

    /**
     * Genera un par de claves RSA 2048-bit, crea un certificado X.509 autofirmado
     * (SHA-256, 5 años), exporta en formato PEM y persiste en el disco central.
     */
    private function buildAndStoreCertificate(Tenant $tenant): void
    {
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => 'bonosweb.com.ar',
            //'organizationalUnitName' => 'Recursos Humanos',
            'commonName'             => $tenant->company_name,
            'emailAddress'           => 'rrhh@' . $tenant->id . '.bonosweb.com',
        ];

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr  = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privkey, 1825, ['digest_alg' => 'sha256']);

        openssl_pkey_export($privkey, $pkeyout);
        openssl_x509_export($x509, $certout);

        // Almacenar en disco central (storage/app/private/certs/)
        $certName = 'certs/' . $tenant->id . '_cert.crt';
        $keyName  = 'certs/' . $tenant->id . '_cert.key';

        Storage::disk('local')->put($certName, $certout);
        Storage::disk('local')->put($keyName, $pkeyout);

        $tenant->cert_path     = $certName;
        $tenant->cert_key_path = $keyName;
        $tenant->cert_expiry   = now()->addYears(5)->toDateString();
        $tenant->save();

        session()->flash('message', "Certificado criptográfico generado para {$tenant->company_name}.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Gestión de certificados PFX por subempresa (Company)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Abre el modal de gestión de firmas para un tenant.
     * Consulta la tabla companies dentro del contexto del tenant.
     */
    public function openCompanyCertModal(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        $this->tenantCompanies = $tenant->run(function () {
            return \App\Models\Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->select('id', 'name', 'cuit', 'is_main', 'signature_pfx_path')
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id'       => $c->id,
                    'name'     => $c->name,
                    'cuit'     => $c->cuit,
                    'is_main'  => $c->is_main,
                    'has_cert' => !empty($c->signature_pfx_path),
                ])
                ->toArray();
        });

        $this->certTenantId       = $tenantId;
        $this->showCompanyCertModal = true;
    }

    public function closeCompanyCertModal(): void
    {
        $this->showCompanyCertModal = false;
        $this->certTenantId         = null;
        $this->tenantCompanies      = [];
    }

    /**
     * Genera un nuevo certificado PFX para una Company que aún no tiene firma.
     */
    public function generateCompanyCert(string $tenantId, int $companyId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->buildAndStorePfx($tenant, $companyId);

        // Refresca el listado en el modal
        $this->openCompanyCertModal($tenantId);
        session()->flash('message', "Certificado PFX generado para la empresa #{$companyId}.");
    }

    /**
     * Regenera (reemplaza) el certificado PFX de una Company existente.
     */
    public function regenerateCompanyCert(string $tenantId, int $companyId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Eliminar el PFX previo del disco del tenant
        $tenant->run(function () use ($companyId) {
            $company = \App\Models\Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->findOrFail($companyId);

            if ($company->signature_pfx_path
                && Storage::disk('local')->exists($company->signature_pfx_path)) {
                Storage::disk('local')->delete($company->signature_pfx_path);
            }
        });

        $this->buildAndStorePfx($tenant, $companyId);

        // Refresca el listado en el modal
        $this->openCompanyCertModal($tenantId);
        session()->flash('message', "Certificado PFX renovado para la empresa #{$companyId}.");
    }

    /**
     * Genera par RSA 2048 + X.509 autofirmado (SHA-256, 10 años),
     * los empaqueta en un archivo PKCS#12 (.pfx) SIN contraseña y los persiste
     * en el disco del tenant (FilesystemTenancyBootstrapper activo en run()).
     *
     * Flujo:
     *  1. Leer datos de la Company desde la BD del tenant.
     *  2. Generar clave + cert en memoria (sin I/O aún).
     *  3. Exportar como PFX con openssl_pkcs12_export().
     *  4. Dentro de $tenant->run(): escribir el .pfx en Storage::disk('local')
     *     (que en contexto tenant apunta a storage/tenant{id}/app/private/)
     *     y actualizar Company.signature_pfx_path con la ruta relativa.
     */
    private function buildAndStorePfx(Tenant $tenant, int $companyId): void
    {
        // 1. Datos de la subempresa
        $companyData = $tenant->run(function () use ($companyId) {
            $c = \App\Models\Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->findOrFail($companyId);
            return ['name' => $c->name, 'cuit' => $c->cuit];
        });

        // 2. Generar par de claves y certificado X.509 (en memoria, contexto central)
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => 'bonosweb.com.ar',
            //'organizationalUnitName' => 'Recursos Humanos',
            'commonName'             => $companyData['name'],
            'emailAddress'           => 'rrhh@' . $tenant->id . '.bonosweb.com',
        ];

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr  = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
        
        // Cargar Certificado y Clave Raíz (Root CA) central
        $rootCertPath = base_path('storage/app/private/certs/system_cert.crt');
        $rootKeyPath  = base_path('storage/app/private/certs/system_cert.key');

        if (!file_exists($rootCertPath) || !file_exists($rootKeyPath)) {
            throw new \RuntimeException('No se encontró el certificado o llave raíz de BonosWeb CA. Ejecute bonosweb:generate-root-ca.');
        }

        $rootCert = file_get_contents($rootCertPath);
        $rootKey  = file_get_contents($rootKeyPath);

        // Firmar con Root CA
        $x509 = openssl_csr_sign($csr, $rootCert, $rootKey, 1825, ['digest_alg' => 'sha256']);

        // 3. Exportar como PKCS#12 (cert + key en un solo archivo, sin contraseña)
        // Incluimos el Root CA en 'extracerts'
        openssl_pkcs12_export($x509, $pfxContent, $privkey, '', ['extracerts' => $rootCert]);

        // 4. Persistir en el disco del tenant y actualizar la BD
        $pfxRelPath = sprintf('certs/company_%d_%s.pfx', $companyId, now()->format('Ymd_His'));

        $tenant->run(function () use ($companyId, $pfxContent, $pfxRelPath) {
            // Storage::disk('local') en contexto tenant apunta a
            // storage/tenant{id}/app/ — no requiere symlink.
            Storage::disk('local')->put($pfxRelPath, $pfxContent);

            \App\Models\Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->where('id', $companyId)
                ->update([
                    'signature_pfx_path'     => $pfxRelPath,
                    'signature_pfx_password' => '', // sin contraseña: la clave no está cifrada
                ]);
        });
    }

    public function openEditModal($id)
    {
        $tenant = Tenant::findOrFail($id);

        $this->editTenantId      = $id;
        $this->editCompanyName   = $tenant->company_name;
        $this->editEmployerCuit  = $tenant->employer_cuit ?? '';
        $this->editAdminName     = $tenant->admin_name ?? '';
        $this->editAdminEmail    = $tenant->admin_email ?? '';
        $this->editAdminPassword = '';
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editTenantId  = null;
    }

    public function saveTenantEdit()
    {
        $this->validate([
            'editCompanyName'   => 'required|string|max:255',
            'editEmployerCuit'  => ['nullable', 'regex:/^(20|23|24|27|30|33|34)\d{9}$/'],
            'editAdminName'     => 'required|string|max:255',
            'editAdminEmail'    => 'required|email|max:255',
            'editAdminPassword' => 'nullable|min:6',
        ], [
            'editEmployerCuit.regex' => 'El CUIT debe tener 11 dígitos numéricos válidos (sin guiones).',
        ]);

        $tenant   = Tenant::findOrFail($this->editTenantId);
        $oldEmail = $tenant->admin_email;

        // Actualizar datos centrales del tenant
        $tenant->company_name  = $this->editCompanyName;
        $tenant->employer_cuit = preg_replace('/\D/', '', $this->editEmployerCuit) ?: null;
        $tenant->admin_name    = $this->editAdminName;
        $tenant->admin_email   = $this->editAdminEmail;
        $tenant->save();

        // Actualizar el usuario administrador en la BD del tenant
        $adminName     = $this->editAdminName;
        $adminEmail    = $this->editAdminEmail;
        $adminPassword = $this->editAdminPassword;

        $tenant->run(function () use ($oldEmail, $adminName, $adminEmail, $adminPassword) {
            $user = \App\Models\User::where('email', $oldEmail)->first()
                 ?? \App\Models\User::orderBy('id')->first();

            if ($user) {
                $user->name  = $adminName;
                $user->email = $adminEmail;
                if ($adminPassword) {
                    $user->password = bcrypt($adminPassword);
                }
                $user->save();
            }
        });

        session()->flash('message', "Datos de {$tenant->company_name} actualizados correctamente.");
        $this->closeEditModal();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Borrado Total (Hard Delete)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Abre el modal de confirmación con el tenant a eliminar pre-cargado.
     */
    public function confirmTenantDeletion(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);

        $this->deleteTenantId          = $tenantId;
        $this->deleteTenantName        = $tenant->company_name ?? $tenantId;
        $this->deleteConfirmationInput = '';
        $this->resetErrorBag('deleteConfirmationInput');
        $this->showDeleteModal         = true;
    }

    /**
     * Cierra el modal sin borrar nada.
     */
    public function cancelTenantDeletion(): void
    {
        $this->showDeleteModal          = false;
        $this->deleteTenantId           = null;
        $this->deleteTenantName         = null;
        $this->deleteConfirmationInput  = '';
    }

    /**
     * Ejecuta el borrado total e irreversible de la infraestructura del tenant:
     *  1. DROP DATABASE (la BD del tenant en MySQL).
     *  2. Eliminación recursiva del directorio de storage (recibos, logos, PFX).
     *  3. Borrado de los certificados CRT/KEY centrales del tenant.
     *  4. Eliminación del registro en la tabla `tenants` (y dominios en cascada).
     *
     * El método es tolerante: si la BD o el directorio ya no existen, continúa
     * con los pasos siguientes en lugar de arrojar un error 500.
     */
    public function deleteTenant(): void
    {
        if (trim($this->deleteConfirmationInput) !== 'ELIMINAR') {
            $this->addError('deleteConfirmationInput', 'Escribí exactamente la palabra ELIMINAR para confirmar.');
            return;
        }

        $tenant = Tenant::find($this->deleteTenantId);

        if (! $tenant) {
            session()->flash('error', 'El tenant ya no existe en el sistema.');
            $this->cancelTenantDeletion();
            return;
        }

        $tenantName = $tenant->company_name ?? $tenant->id;

        try {
            // ── 1. Eliminar base de datos ─────────────────────────────────────
            // Si la BD no existía (tenant a medio crear) el catch interno la ignora.
            try {
                $tenant->database()->manager()->deleteDatabase($tenant);
            } catch (\Throwable $dbEx) {
                Log::warning('[deleteTenant] La BD del tenant no existía o no pudo borrarse. Se continúa.', [
                    'tenant' => $tenant->id,
                    'error'  => $dbEx->getMessage(),
                ]);
            }

            // ── 2. Eliminar directorio de storage físico ──────────────────────
            // stancl/tenancy guarda los archivos en storage/tenant{id}/
            $tenantStoragePath = storage_path('tenant' . $tenant->id);
            if (File::isDirectory($tenantStoragePath)) {
                File::deleteDirectory($tenantStoragePath);
            }

            // ── 3. Eliminar certificados CRT/KEY centrales del tenant ─────────
            foreach ([$tenant->cert_path, $tenant->cert_key_path] as $certFile) {
                if ($certFile && Storage::disk('local')->exists($certFile)) {
                    Storage::disk('local')->delete($certFile);
                }
            }

            // ── 4. Eliminar dominios y registro central ───────────────────────
            $tenant->domains()->delete();
            $tenant->delete();

            session()->flash('message', "Empresa \u00abBORRADA\u00bb: {$tenantName}. BD, storage y registro central eliminados permanentemente.");
            Log::info('[deleteTenant] Tenant eliminado completamente.', ['tenant_id' => $this->deleteTenantId]);

        } catch (\Throwable $e) {
            Log::error('[deleteTenant] Error durante el borrado total.', [
                'tenant' => $tenant->id,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Error al eliminar: ' . $e->getMessage());
        }

        $this->cancelTenantDeletion();
    }

    public function render()
    {
        $tenants = Tenant::with('domains')
            ->when($this->search, fn ($q) => $q->where('company_name', 'like', "%{$this->search}%"))
            ->paginate(10);

        // Consultar empleados activos en la BD de cada tenant de la página actual.
        // $tenant->run() inicializa el contexto del tenant, ejecuta el callback
        // y revierte automáticamente al contexto central.
        $employeeCounts = [];
        foreach ($tenants->getCollection() as $tenant) {
            try {
                // distinct('user_id') garantiza que un empleado que trabaja en
                // múltiples subempresas del mismo tenant cuente UNA sola vez.
                $employeeCounts[$tenant->id] = $tenant->run(function () {
                    return \App\Models\EmployeeProfile::withoutGlobalScope(CurrentCompanyScope::class)
                        ->where('is_active', true)
                        ->distinct('user_id')
                        ->count('user_id');
                });
            } catch (\Throwable $e) {
                $employeeCounts[$tenant->id] = 0;
            }
        }

        return view('livewire.superadmin.tenants-manager', [
            'tenants'        => $tenants,
            'employeeCounts' => $employeeCounts,
        ])->layout('components.layouts.superadmin', [
            'header' => 'Gestión de Empresas (Tenants)',
            'title' => 'Empresas - BonosWeb Central'
        ]);
    }
}
