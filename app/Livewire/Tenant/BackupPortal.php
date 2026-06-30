<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Company;
use App\Models\BackupDownloadLog;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupPortal extends Component
{
    public $companyId;
    public $fileCount = 0;
    public $totalSize = 0; // en bytes

    public function mount()
    {
        $this->companyId = app(\App\Services\CompanyContextService::class)->getCurrentCompanyId();
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $tenantName = tenant('company_name') ?? tenant('id') ?? 'UnknownTenant';
        $companyName = Company::find($this->companyId)->name ?? 'UnknownCompany';
        
        // El prefijo base en GCS
        $prefix = "{$tenantName}/{$companyName}/";
        
        $files = Storage::disk('gcs')->allFiles($prefix);
        
        $this->fileCount = count($files);
        
        $size = 0;
        foreach ($files as $file) {
            // Caching file size to avoid too many API calls if possible, but for stats it's fine
            // In a huge bucket, allFiles() might be slow.
            // Using size is optional but good for UI.
            $size += Storage::disk('gcs')->size($file);
        }
        $this->totalSize = $size;
    }

    public function downloadZip()
    {
        if ($this->fileCount === 0) {
            session()->flash('error', 'No hay copias de seguridad disponibles para esta empresa.');
            return;
        }

        $tenantName = tenant('company_name') ?? tenant('id') ?? 'UnknownTenant';
        $companyName = Company::find($this->companyId)->name ?? 'UnknownCompany';
        
        $prefix = "{$tenantName}/{$companyName}/";
        $files = Storage::disk('gcs')->allFiles($prefix);

        if (empty($files)) {
            session()->flash('error', 'No se encontraron archivos en GCS.');
            return;
        }

        // Crear ZIP temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'backup_zip');
        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                // Descargar temporalmente el archivo para agregarlo al ZIP
                $content = Storage::disk('gcs')->get($file);
                
                // Extraer el nombre de archivo sin los directorios intermedios
                // O mantener la estructura de año/mes/tipo
                // $file es de la forma "Tenant/Company/2026/06/Mensual/CUIL/Lote/recibo.pdf"
                // Vamos a mantener la estructura relativa a la empresa
                $relativePath = str_replace($prefix, '', $file);
                
                $zip->addFromString($relativePath, $content);
            }
            $zip->close();
        }

        // Registrar auditoría
        BackupDownloadLog::create([
            'tenant_id' => tenant('id'),
            'company_id' => $this->companyId,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $zipName = "Backup_{$companyName}_" . date('Y-m-d_His') . ".zip";

        return response()->download($tempFile, $zipName)->deleteFileAfterSend(true);
    }

    public function render()
    {
        return view('livewire.tenant.backup-portal')->layout('components.layouts.app');
    }
}
