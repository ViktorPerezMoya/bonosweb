<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant;
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
            'subdomain'      => 'required|alpha_dash|unique:domains,domain',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255',
            'admin_password' => 'required|min:6',
        ], [
            'employer_cuit.regex' => 'El CUIT debe tener 11 dígitos numéricos válidos (sin guiones).',
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
     * (SHA-256, 10 años), exporta en formato PEM y persiste en el disco central.
     */
    private function buildAndStoreCertificate(Tenant $tenant): void
    {
        $dn = [
            'countryName'            => 'AR',
            'stateOrProvinceName'    => 'Mendoza',
            'localityName'           => 'Mendoza',
            'organizationName'       => $tenant->company_name,
            'organizationalUnitName' => 'Recursos Humanos',
            'commonName'             => $tenant->company_name,
            'emailAddress'           => 'rrhh@' . $tenant->id . '.bonosweb.com',
        ];

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr  = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privkey, 3650, ['digest_alg' => 'sha256']);

        openssl_pkey_export($privkey, $pkeyout);
        openssl_x509_export($x509, $certout);

        // Almacenar en disco central (storage/app/private/certs/)
        $certName = 'certs/' . $tenant->id . '_cert.crt';
        $keyName  = 'certs/' . $tenant->id . '_cert.key';

        Storage::disk('local')->put($certName, $certout);
        Storage::disk('local')->put($keyName, $pkeyout);

        $tenant->cert_path     = $certName;
        $tenant->cert_key_path = $keyName;
        $tenant->cert_expiry   = now()->addYears(10)->toDateString();
        $tenant->save();

        session()->flash('message', "Certificado criptográfico generado para {$tenant->company_name}.");
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

    public function render()
    {
        $tenants = Tenant::with('domains')->paginate(10);

        // Consultar empleados activos en la BD de cada tenant de la página actual.
        // $tenant->run() inicializa el contexto del tenant, ejecuta el callback
        // y revierte automáticamente al contexto central.
        $employeeCounts = [];
        foreach ($tenants->getCollection() as $tenant) {
            try {
                $employeeCounts[$tenant->id] = $tenant->run(function () {
                    return \App\Models\EmployeeProfile::where('is_active', true)->count();
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
