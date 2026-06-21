<?php

namespace App\Livewire\Payslips;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\ProcessPayslipBatch;
use App\Models\UploadBatch;
use App\Services\CompanyContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Upload extends Component
{
    use WithFileUploads;

    public $zipFile;
    public $period_year;
    public $period_month;
    public $liquidation_type = 'mensual';
    public $notification_date;
    public $isUploading = false;
    public $uploadSuccess = false;
    public $showErrorModal = false;
    public $errorMessage = '';
    
    // Signature validation properties
    public $showSignatureWarningModal = false;
    public $showSignaturePreviewModal = false;
    public $signaturePreviewUrl = null;

    public function mount()
    {
        $this->period_year = date('Y');
        $this->period_month = date('m');
        $this->notification_date = date('Y-m-d\TH:i'); // default now
    }

    protected $rules = [
        'zipFile' => 'required|file|mimes:zip,pdf|max:51200', // max 50MB
        'period_year' => 'required|integer|min:2020|max:2050',
        'period_month' => 'required|integer|min:1|max:12',
        'liquidation_type' => 'required|in:mensual,quincena,anticipo,sac,vacaciones,gratificacion,final,retroactivo',
        'notification_date' => 'required|date',
    ];

    protected $messages = [
        'zipFile.required' => 'Debes seleccionar un archivo ZIP o PDF.',
        'zipFile.mimes'    => 'El archivo debe ser un formato .zip o .pdf válido.',
        'zipFile.max'      => 'El archivo no debe pesar más de 50MB.',
    ];

    public function save()
    {
        $this->validate();

        try {
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = \App\Models\Company::find($companyId);

            // 1. Verificamos si existe firma
            if (empty($company->signature_image_path)) {
                $this->showSignatureWarningModal = true;
                return;
            }

            // 2. Preparamos previsualización
            if ($company->signature_preview_path) {
                $this->signaturePreviewUrl = route('signature.preview.rendered') . '?v=' . time();
            } else {
                $this->signaturePreviewUrl = null;
            }

            // 3. Mostramos modal de confirmación visual
            $this->showSignaturePreviewModal = true;

        } catch (\Exception $e) {
            $this->errorMessage = "Error verificando configuración: " . $e->getMessage();
            $this->showErrorModal = true;
        }
    }

    public function proceedWithUpload()
    {
        $this->showSignaturePreviewModal = false;

        try {
            // 1. Detectar tipo de archivo y almacenar
            $extension = strtolower($this->zipFile->getClientOriginalExtension());
            $fileType  = $extension === 'pdf' ? 'pdf' : 'zip';
            $path = $this->zipFile->store('batches/temp', 'local');

            // Empresa activa en la sesión del usuario de RRHH
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = \App\Models\Company::find($companyId);
            $rotationAngle = $company->pdf_rotation ?? 0;

            // Normalizar orientación si es un PDF individual/sábana
            if ($fileType === 'pdf') {
                $absolutePath = Storage::disk('local')->path($path);
                $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $pythonBin = $isWindows ? 'python' : 'python3';
                $scriptPath = base_path('storage/scripts/normalize_rotation.py');
                
                $env = $isWindows ? [
                    'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows', 
                    'PATH' => getenv('PATH')
                ] : null;

                $process = new \Symfony\Component\Process\Process(
                    [$pythonBin, $scriptPath, $absolutePath, $absolutePath, $rotationAngle],
                    null,
                    $env
                );
                
                $process->run();
                
                if (!$process->isSuccessful()) {
                    \Illuminate\Support\Facades\Log::error("Error normalizando rotación del PDF: " . $process->getErrorOutput());
                    throw new \Exception('Ocurrió un error al normalizar la orientación del documento.');
                }
            }

            // 2. Crear el registro del Batch en la Base de Datos
            $batch = UploadBatch::create([
                'uploader_id'       => Auth::id(),
                'company_id'        => $companyId,
                'original_filename' => $this->zipFile->getClientOriginalName(),
                'file_type'         => $fileType,
                'period_year'       => $this->period_year,
                'period_month'      => $this->period_month,
                'liquidation_type'  => $this->liquidation_type,
                'notification_date' => $this->notification_date,
                'notifications_sent' => false,
                'status'            => 'pending',
                'total_files'       => 0,
                'processed_files'   => 0,
            ]);

            // 3. Despachar el Job con el ID de empresa explícito para que el
            //    proceso asíncrono (sin sesión HTTP) use el certificado correcto.
            ProcessPayslipBatch::dispatch($batch, $path, $companyId);

            $this->uploadSuccess = true;
            $this->reset('zipFile');

            session()->flash('message', 'El archivo ha sido subido con éxito y está en cola para ser procesado.');

        } catch (\Exception $e) {
            $this->errorMessage = "Error del sistema: " . $e->getMessage();
            $this->showErrorModal = true;
        }
    }

    public function showUploadError()
    {
        $this->errorMessage = "El archivo no pudo ser subido. Verifica que no exceda el límite de tamaño (50MB) o intenta nuevamente.";
        $this->showErrorModal = true;
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

    public function closeSignatureWarningModal()
    {
        $this->showSignatureWarningModal = false;
    }

    public function closeSignaturePreviewModal()
    {
        $this->showSignaturePreviewModal = false;
    }

    public function render()
    {
        return view('livewire.payslips.upload')->layout('components.layouts.app', [
            'header' => 'Subida de Recibos de Sueldo',
            'title' => 'Subir Bonos - BonosWeb'
        ]);
    }
}
