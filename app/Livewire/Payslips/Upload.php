<?php

namespace App\Livewire\Payslips;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\UploadBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Jobs\ProcessPayslipBatch;

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

    public function mount()
    {
        $this->period_year = date('Y');
        $this->period_month = date('m');
        $this->notification_date = date('Y-m-d\TH:i'); // default now
    }

    protected $rules = [
        'zipFile' => 'required|file|mimes:zip|max:51200', // max 50MB
        'period_year' => 'required|integer|min:2020|max:2050',
        'period_month' => 'required|integer|min:1|max:12',
        'liquidation_type' => 'required|in:mensual,sac,vacaciones,gratificacion,final,retroactivo',
        'notification_date' => 'required|date',
    ];

    protected $messages = [
        'zipFile.required' => 'Debes seleccionar un archivo ZIP.',
        'zipFile.mimes' => 'El archivo debe ser un formato .zip válido.',
        'zipFile.max' => 'El archivo no debe pesar más de 50MB.',
    ];

    public function save()
    {
        $this->validate();

        try {
            // 1. Guardar el ZIP temporalmente
            $path = $this->zipFile->store('batches/temp', 'local');

            // 2. Crear el registro del Batch en la Base de Datos
            $batch = UploadBatch::create([
                'uploader_id' => Auth::id(),
                'original_filename' => $this->zipFile->getClientOriginalName(),
                'period_year' => $this->period_year,
                'period_month' => $this->period_month,
                'liquidation_type' => $this->liquidation_type,
                'notification_date' => $this->notification_date,
                'notifications_sent' => false,
                'status' => 'pending',
                'total_files' => 0, // Se actualizará en el Job
                'processed_files' => 0,
            ]);

            // 3. Despachar el Job para procesar en background
            ProcessPayslipBatch::dispatch($batch, $path);

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

    public function render()
    {
        return view('livewire.payslips.upload')->layout('components.layouts.app', [
            'header' => 'Subida de Recibos de Sueldo',
            'title' => 'Subir Bonos - BonosWeb'
        ]);
    }
}
