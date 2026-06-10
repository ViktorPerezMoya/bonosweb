<?php

namespace App\Livewire\Tenant;

use Livewire\Component;

class RootCertificateDownload extends Component
{
    public function mount()
    {
        if (!in_array(auth()->user()->role, ['admin', 'rrhh'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
    }
    /**
     * Descarga el Certificado Raíz de BonosWeb.
     * Lee directamente de storage/app/private/certs/system_cert.crt
     */
    public function download()
    {
        $path = base_path('storage/app/private/certs/system_cert.crt');

        if (!file_exists($path)) {
            session()->flash('error', 'El certificado raíz no se encuentra disponible en el servidor.');
            return;
        }

        return response()->download($path, 'BonosWeb_Root_CA.crt', [
            'Content-Type' => 'application/x-x509-ca-cert',
        ]);
    }

    public function render()
    {
        return view('livewire.tenant.root-certificate-download')->layout('components.layouts.app', [
            'header' => 'Certificado Raíz',
            'title'  => 'Descargar Certificado - BonosWeb'
        ]);
    }
}
