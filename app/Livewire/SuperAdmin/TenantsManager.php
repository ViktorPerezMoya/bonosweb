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
    public $company_name = '';
    public $subdomain    = '';
    public $admin_name   = '';
    public $admin_email  = '';
    public $admin_password = '';

    public function openModal()
    {
        $this->company_name = '';
        $this->subdomain = '';
        $this->admin_name = '';
        $this->admin_email = '';
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
            'company_name' => 'required|string|max:255',
            'subdomain' => 'required|alpha_dash|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|min:6',
        ]);

        // Asegurar que el subdominio termine con bonosweb.com o localhost para dev
        $baseDomain = env('APP_ENV') === 'local' ? 'localhost' : 'bonosweb.com';
        $fullDomain = $this->subdomain . '.' . $baseDomain;

        // Crear el tenant. Esto dispara la creación de la DB automáticamente gracias a stancl/tenancy
        $tenant = Tenant::create([
            'id'           => $this->subdomain,
            'company_name' => $this->company_name,
            'admin_name'   => $this->admin_name,
            'admin_email'  => $this->admin_email,
            'admin_password' => bcrypt($this->admin_password),
            'is_suspended' => false,
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
        
        // Generar un certificado OpenSSL propio para la empresa
        $dn = array(
            "countryName" => "AR",
            "stateOrProvinceName" => "Mendoza",
            "localityName" => "Mendoza",
            "organizationName" => $tenant->company_name,
            "organizationalUnitName" => "Recursos Humanos",
            "commonName" => $tenant->company_name,
            "emailAddress" => "rrhh@" . $tenant->id . ".bonosweb.com"
        );

        // Generar clave privada y CSR
        $privkey = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));
        
        $csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'sha256'));
        
        // Certificado autofirmado (Válido por 10 años)
        $x509 = openssl_csr_sign($csr, null, $privkey, 3650, array('digest_alg' => 'sha256'));
        
        openssl_pkey_export($privkey, $pkeyout);
        openssl_x509_export($x509, $certout);

        // Guardar en storage seguro (Central Storage)
        $certName = 'certs/' . $tenant->id . '_cert.crt';
        $keyName = 'certs/' . $tenant->id . '_cert.key';
        
        Storage::disk('local')->put($certName, $certout);
        Storage::disk('local')->put($keyName, $pkeyout);

        // Actualizar el Tenant
        $tenant->cert_path = $certName;
        $tenant->cert_key_path = $keyName;
        $tenant->save();

        session()->flash('message', "Certificado criptográfico generado para {$tenant->company_name}.");
    }

    public function render()
    {
        return view('livewire.superadmin.tenants-manager', [
            'tenants' => Tenant::with('domains')->paginate(10)
        ])->layout('components.layouts.superadmin', [
            'header' => 'Gestión de Empresas (Tenants)',
            'title' => 'Empresas - BonosWeb Central'
        ]);
    }
}
