<?php

namespace App\Jobs;

use App\Models\UploadBatch;
use App\Models\Payslip;
use App\Models\EmployeeProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use ZipArchive;
use Exception;

class ProcessPayslipBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;
    protected $zipPath;
    
    /**
     * El tiempo máximo que el job puede ejecutar (en segundos).
     */
    public $timeout = 3600; // 1 hora máximo para lotes grandes

    public function __construct(UploadBatch $batch, $zipPath)
    {
        $this->batch = $batch;
        $this->zipPath = $zipPath;
    }

    public function handle()
    {
        $this->batch->update(['status' => 'processing']);
        
        $zipFullPath = Storage::disk('local')->path($this->zipPath);
        $extractPath = Storage::disk('local')->path('batches/extracted_' . $this->batch->id);
        
        $zip = new ZipArchive;
        $res = $zip->open($zipFullPath);
        
        if ($res !== TRUE) {
            $this->markAsFailed("No se pudo abrir el archivo ZIP. Código de error: " . $res);
            return;
        }

        // Crear directorio de extracción
        if (!Storage::disk('local')->exists('batches/extracted_' . $this->batch->id)) {
            Storage::disk('local')->makeDirectory('batches/extracted_' . $this->batch->id);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Obtener todos los PDFs extraídos
        $files = Storage::disk('local')->allFiles('batches/extracted_' . $this->batch->id);
        $pdfFiles = array_filter($files, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });

        $this->batch->update(['total_files' => count($pdfFiles)]);
        
        $pdfParser = new Parser();
        $errors = [];
        $processed = 0;

        foreach ($pdfFiles as $file) {
            try {
                $fullPdfPath = Storage::disk('local')->path($file);
                
                // 1. Leer el texto del PDF
                $pdf = $pdfParser->parseFile($fullPdfPath);
                $text = $pdf->getText();
                
                // 2. Buscar el CUIL mediante Regex (Ej formato: 20-12345678-9 o 20123456789)
                // Buscamos un patrón de 11 dígitos, opcionalmente separados por guiones
                preg_match('/\b(20|23|24|27)([-_ ]?)(\d{8})\2(\d)\b/', $text, $matches);
                
                if (empty($matches)) {
                    $errors[] = "No se encontró CUIL en el archivo: " . basename($file);
                    continue;
                }
                
                // Limpiar el CUIL encontrado
                $cuil = $matches[1] . $matches[3] . $matches[4]; // Ej: 20123456789
                
                // 3. Buscar al empleado en la base de datos
                // Nota: El CUIL en DB debe estar guardado limpio sin guiones, o adaptar la búsqueda
                $employeeProfile = EmployeeProfile::where('cuil', $cuil)
                    ->orWhere('cuil', $matches[0]) // Por si se guardó con guiones
                    ->first();
                
                if (!$employeeProfile) {
                    $errors[] = "CUIL {$cuil} no encontrado en la BD para el archivo: " . basename($file);
                    continue;
                }
                
                // 4. Calcular el Hash SHA-256 Inmutable
                $fileHash = hash_file('sha256', $fullPdfPath);
                
                // 5. Mover a su ubicación final segura
                $periodYear = $this->batch->period_year;
                $periodMonth = str_pad($this->batch->period_month, 2, '0', STR_PAD_LEFT);
                $finalPath = "payslips/{$periodYear}-{$periodMonth}/{$fileHash}.pdf";
                
                Storage::disk('local')->copy($file, $finalPath);
                
                // LÓGICA DE RECTIFICATIVA INMUTABLE
                // Buscar si ya existe un recibo activo para este periodo y tipo
                $existingPayslip = Payslip::where('employee_id', $employeeProfile->user_id)
                    ->where('period_year', $this->batch->period_year)
                    ->where('period_month', $this->batch->period_month)
                    ->where('liquidation_type', $this->batch->liquidation_type)
                    ->where('is_rectified', false)
                    ->first();
                
                // 6. Guardar el registro NUEVO
                $newPayslip = Payslip::create([
                    'employee_id' => $employeeProfile->user_id,
                    'upload_batch_id' => $this->batch->id,
                    'period_year' => $this->batch->period_year,
                    'period_month' => $this->batch->period_month,
                    'liquidation_type' => $this->batch->liquidation_type,
                    'file_path' => $finalPath,
                    'original_filename' => basename($file),
                    'file_hash' => $fileHash,
                    'status' => 'pending',
                    'is_rectified' => false
                ]);
                
                // Si existía uno previo, lo marcamos como rectificado
                if ($existingPayslip) {
                    $existingPayslip->update([
                        'is_rectified' => true,
                        'rectified_by_id' => $newPayslip->id
                    ]);
                    // Nota: Las firmas previas en 'signatures' quedan intactas por valor legal.
                }
                
                $processed++;
                
                // Actualizar progreso cada 10 archivos para no saturar la BD
                if ($processed % 10 == 0) {
                    $this->batch->update(['processed_files' => $processed]);
                }
                
            } catch (Exception $e) {
                $errors[] = "Error crítico procesando " . basename($file) . ": " . $e->getMessage();
            }
        }

        // Finalizar
        $this->batch->update([
            'processed_files' => $processed,
            'status' => 'completed',
            'error_log' => empty($errors) ? null : implode("\n", $errors)
        ]);

        // Limpieza
        Storage::disk('local')->deleteDirectory('batches/extracted_' . $this->batch->id);
        Storage::disk('local')->delete($this->zipPath);
    }

    protected function markAsFailed($reason)
    {
        $this->batch->update([
            'status' => 'failed',
            'error_log' => $reason
        ]);
    }
}
