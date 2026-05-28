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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;
use ZipArchive;
use Exception;

class ProcessPayslipBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;
    protected $filePath;
    protected array|null $sigConfig = null;

    public $timeout = 3600;

    public function __construct(UploadBatch $batch, string $filePath)
    {
        $this->batch    = $batch;
        $this->filePath = $filePath;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): void
    {
        // Cargar configuración del Tenant una sola vez antes del bucle de procesamiento
        $this->loadSignatureConfig();

        $this->batch->update(['status' => 'processing']);

        if ($this->batch->file_type === 'pdf') {
            $this->handleMassivePdf();
        } else {
            $this->handleZip();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estrategia ZIP (lógica original preservada íntegramente)
    // ─────────────────────────────────────────────────────────────────────────

    protected function handleZip(): void
    {
        $zipFullPath = Storage::disk('local')->path($this->filePath);
        $extractDir  = 'batches/extracted_' . $this->batch->id;
        $extractPath = Storage::disk('local')->path($extractDir);

        $zip = new ZipArchive;
        if ($zip->open($zipFullPath) !== true) {
            $this->markAsFailed('No se pudo abrir el archivo ZIP.');
            return;
        }

        if (!Storage::disk('local')->exists($extractDir)) {
            Storage::disk('local')->makeDirectory($extractDir);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $files    = Storage::disk('local')->allFiles($extractDir);
        $pdfFiles = array_values(array_filter(
            $files,
            fn($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf'
        ));

        $this->batch->update(['total_files' => count($pdfFiles)]);

        $employerCuit = $this->getEmployerCuit();
        $pdfParser    = new Parser();
        $errors       = [];
        $processed    = 0;

        foreach ($pdfFiles as $file) {
            try {
                $fullPath = Storage::disk('local')->path($file);
                $text     = $pdfParser->parseFile($fullPath)->getText();

                [$cuil, $rawCuil] = $this->extractEmployeeCuil($text, $employerCuit);

                if (!$cuil) {
                    $errors[] = 'No se encontro CUIL de empleado en: ' . basename($file);
                    continue;
                }

                $profile = $this->findEmployeeProfile($cuil, $rawCuil);

                if (!$profile) {
                    $errors[] = "CUIL {$cuil} no encontrado en la BD para: " . basename($file);
                    continue;
                }

                $processed += (int) $this->persistPayslip($profile, $fullPath, basename($file));

            } catch (Exception $e) {
                $errors[] = 'Error procesando ' . basename($file) . ': ' . $e->getMessage();
            }

            if ($processed % 10 === 0 && $processed > 0) {
                $this->batch->update(['processed_files' => $processed]);
            }
        }

        $this->finalize($processed, $errors);

        Storage::disk('local')->deleteDirectory($extractDir);
        Storage::disk('local')->delete($this->filePath);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estrategia PDF masivo (nueva logica)
    // ─────────────────────────────────────────────────────────────────────────

    protected function handleMassivePdf(): void
    {
        $fullPdfPath = Storage::disk('local')->path($this->filePath);
        $extractDir  = 'batches/extracted_' . $this->batch->id;

        if (!Storage::disk('local')->exists($extractDir)) {
            Storage::disk('local')->makeDirectory($extractDir);
        }

        $pdfParser = new Parser();
        try {
            $parsedPdf = $pdfParser->parseFile($fullPdfPath);
        } catch (Exception $e) {
            $this->markAsFailed('No se pudo leer el PDF masivo: ' . $e->getMessage());
            return;
        }

        $pages     = $parsedPdf->getPages();
        $pageCount = count($pages);

        if ($pageCount === 0) {
            $this->markAsFailed('El PDF masivo no contiene paginas legibles.');
            return;
        }

        $this->batch->update(['total_files' => $pageCount]);

        $employerCuit = $this->getEmployerCuit();
        $errors       = [];
        $processed    = 0;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            try {
                $pageText = $pages[$pageNo - 1]->getText();

                [$cuil, $rawCuil] = $this->extractEmployeeCuil($pageText, $employerCuit);

                if (!$cuil) {
                    $errors[] = "Pagina {$pageNo}: no se encontro CUIL de empleado.";
                    continue;
                }

                $profile = $this->findEmployeeProfile($cuil, $rawCuil);

                if (!$profile) {
                    $errors[] = "Pagina {$pageNo}: CUIL {$cuil} no encontrado en la BD.";
                    continue;
                }

                $pagePath = $this->extractSinglePage($fullPdfPath, $pageNo, $extractDir);

                if (!$pagePath) {
                    $errors[] = "Pagina {$pageNo}: no se pudo extraer como archivo independiente. "
                        . "Verifique que el PDF no use streams comprimidos (PDF 1.5+).";
                    continue;
                }

                $absolutePagePath = Storage::disk('local')->path($pagePath);
                $originalName     = 'pagina_' . str_pad($pageNo, 4, '0', STR_PAD_LEFT) . '.pdf';

                $processed += (int) $this->persistPayslip($profile, $absolutePagePath, $originalName);

                Storage::disk('local')->delete($pagePath);

            } catch (Exception $e) {
                $errors[] = "Pagina {$pageNo}: error critico - " . $e->getMessage();
            }

            if ($processed % 10 === 0 && $processed > 0) {
                $this->batch->update(['processed_files' => $processed]);
            }
        }

        $this->finalize($processed, $errors);

        Storage::disk('local')->deleteDirectory($extractDir);
        Storage::disk('local')->delete($this->filePath);
    }

    /**
     * Extrae una sola pagina del PDF masivo como archivo independiente.
     * Usa FPDI + TCPDF. Retorna el path relativo al disco local, o null
     * si el PDF usa streams comprimidos no soportados por FPDI free.
     */
    protected function extractSinglePage(string $sourcePath, int $pageNo, string $extractDir): ?string
    {
        try {
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($sourcePath);

            $templateId = $fpdi->importPage($pageNo);
            $size       = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage(
                $size['orientation'] ?? 'P',
                [$size['width'], $size['height']]
            );
            $fpdi->useTemplate($templateId);

            $relativePath = $extractDir . '/page_' . str_pad($pageNo, 4, '0', STR_PAD_LEFT) . '.pdf';
            $fpdi->Output(Storage::disk('local')->path($relativePath), 'F');

            unset($fpdi);

            return $relativePath;

        } catch (Exception $e) {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Metodos compartidos
    // ─────────────────────────────────────────────────────────────────────────

    protected function getEmployerCuit(): string
    {
        return preg_replace('/\D/', '', tenant('employer_cuit') ?? '');
    }

    /**
     * Encuentra el primer CUIL/CUIT en el texto que no sea el del empleador.
     *
     * @return array{0: string|null, 1: string|null}
     */
    protected function extractEmployeeCuil(string $text, string $employerCuit): array
    {
        preg_match_all(
            '/\b(20|23|24|27|30|33|34)([-_ ]?)(\d{8})\2(\d)\b/',
            $text,
            $allMatches,
            PREG_SET_ORDER
        );

        foreach ($allMatches as $match) {
            $clean = $match[1] . $match[3] . $match[4];
            if ($employerCuit && $clean === $employerCuit) {
                continue;
            }
            return [$clean, $match[0]];
        }

        return [null, null];
    }

    protected function findEmployeeProfile(string $cuil, string $rawCuil): ?EmployeeProfile
    {
        return EmployeeProfile::where('cuil', $cuil)
            ->orWhere('cuil', $rawCuil)
            ->first();
    }

    /**
     * Calcula SHA-256, copia el archivo a su ruta final e inserta el Payslip.
     * Aplica rectificativa inmutable: el recibo previo se marca, no se borra.
     */
    protected function persistPayslip(EmployeeProfile $profile, string $absoluteSrcPath, string $originalName): bool
    {
        // Estampar firma visual del empleador (si está configurada)
        $finalContent = $this->stampedPdfContent($absoluteSrcPath);
        $fileHash     = hash('sha256', $finalContent);   // hash del archivo FINAL
        $periodYear   = $this->batch->period_year;
        $periodMonth  = str_pad($this->batch->period_month, 2, '0', STR_PAD_LEFT);
        $finalPath    = "payslips/{$periodYear}-{$periodMonth}/{$fileHash}.pdf";

        Storage::disk('local')->put($finalPath, $finalContent);
        unset($finalContent); // liberar memoria

        $existing = Payslip::where('employee_id', $profile->user_id)
            ->where('period_year', $this->batch->period_year)
            ->where('period_month', $this->batch->period_month)
            ->where('liquidation_type', $this->batch->liquidation_type)
            ->where('is_rectified', false)
            ->first();

        $newPayslip = Payslip::create([
            'employee_id'       => $profile->user_id,
            'upload_batch_id'   => $this->batch->id,
            'period_year'       => $this->batch->period_year,
            'period_month'      => $this->batch->period_month,
            'liquidation_type'  => $this->batch->liquidation_type,
            'file_path'         => $finalPath,
            'original_filename' => $originalName,
            'file_hash'         => $fileHash,
            'status'            => 'pending',
            'is_rectified'      => false,
        ]);

        if ($existing) {
            $existing->update([
                'is_rectified'    => true,
                'rectified_by_id' => $newPayslip->id,
            ]);
        }

        return true;
    }

    protected function finalize(int $processed, array $errors): void
    {
        $this->batch->update([
            'processed_files' => $processed,
            'status'          => 'completed',
            'error_log'       => empty($errors) ? null : implode("\n", $errors),
        ]);
    }

    protected function markAsFailed(string $reason): void
    {
        $this->batch->update([
            'status'    => 'failed',
            'error_log' => $reason,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Firma visual + firma digital del empleador
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Carga UNA SOLA VEZ la configuración de firma del Tenant.
     *
     * La imagen de firma vive en el disco local del tenant (FilesystemTenancyBootstrapper).
     * Los archivos PEM del certificado digital los guarda el SuperAdmin en el storage
     * central (storage_path('app/...')), fuera del alcance del tenant bootstrapper.
     */
    protected function loadSignatureConfig(): void
    {
        $tenant = \App\Models\Tenant::find(tenant('id'));
        if (!$tenant) {
            return;
        }

        $imgRelPath = $tenant->signature_image_path;
        $sigX       = $tenant->signature_x;

        // Sin firma visual configurada → no hay nada que preparar
        if (!$imgRelPath || $sigX === null) {
            return;
        }

        // La imagen vive en el disco del tenant (bootstrapper ya activo en el job)
        $imgAbsPath = Storage::disk('local')->path($imgRelPath);
        if (!file_exists($imgAbsPath)) {
            Log::warning('ProcessPayslipBatch: imagen de firma no encontrada.', [
                'path' => $imgAbsPath,
            ]);
            return;
        }

        $ext = strtoupper(pathinfo($imgAbsPath, PATHINFO_EXTENSION));
        if ($ext === 'JPG') {
            $ext = 'JPEG'; // TCPDF requiere 'JPEG'
        }

        // Certificado digital: guardado en storage CENTRAL por el SuperAdmin.
        // storage_path() en contexto tenant es sobreescrito por Stancl (suffix_storage_path=true).
        // base_path() NO es sobreescrito → única forma de referenciar el storage central.
        $certUri = null;
        $keyUri  = null;

        if ($tenant->cert_path && $tenant->cert_key_path) {
            $centralBase = base_path(
                'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
                . 'private' . DIRECTORY_SEPARATOR
            );
            $certAbsPath = $centralBase . ltrim(
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tenant->cert_path),
                DIRECTORY_SEPARATOR
            );
            $keyAbsPath = $centralBase . ltrim(
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tenant->cert_key_path),
                DIRECTORY_SEPARATOR
            );

            if (file_exists($certAbsPath) && file_exists($keyAbsPath)) {
                $certUri = 'file://' . $certAbsPath;
                $keyUri  = 'file://' . $keyAbsPath;
            } else {
                Log::warning('ProcessPayslipBatch: archivos de certificado digital no encontrados.', [
                    'cert' => $certAbsPath,
                    'key'  => $keyAbsPath,
                ]);
            }
        }

        $this->sigConfig = [
            'img_abs_path' => $imgAbsPath,
            'img_ext'      => $ext,
            'x'            => (float) $tenant->signature_x,
            'y'            => (float) $tenant->signature_y,
            'w'            => (float) $tenant->signature_w,
            'h'            => (float) $tenant->signature_h,
            'cert_uri'     => $certUri,
            'key_uri'      => $keyUri,
            'cert_info'    => [
                'Name'        => $tenant->company_name ?? '',
                'Location'    => 'Argentina',
                'Reason'      => 'Recibo de Sueldo — Firma del Empleador',
                'ContactInfo' => preg_replace('/\D/', '', $tenant->employer_cuit ?? ''),
            ],
        ];
    }

    /**
     * Retorna el contenido del PDF con:
     *  - Imagen de firma visual del empleador (en coordenadas mm A4 configuradas).
     *  - Firma digital criptográfica X.509 del Tenant (PKCS#7, modo incremental).
     *
     * cert_type=2: permite que el empleado agregue su propia firma electrónica
     * posteriormente sin invalidar el sello del empleador.
     *
     * Si la firma no está configurada, o si FPDI no soporta el PDF (compressed
     * streams / PDF 1.5+), retorna el PDF original sin modificaciones.
     */
    protected function stampedPdfContent(string $srcPath): string
    {
        // Sin configuración pre-cargada → devolver el PDF original
        if (!$this->sigConfig) {
            return file_get_contents($srcPath);
        }

        $cfg = $this->sigConfig;

        try {
            $fpdi = new Fpdi();
            $fpdi->setPrintHeader(false);
            $fpdi->setPrintFooter(false);
            $fpdi->setSourceFile($srcPath);

            $templateId = $fpdi->importPage(1);
            $size       = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage(
                $size['orientation'] ?? 'P',
                [$size['width'], $size['height']]
            );
            $fpdi->useTemplate($templateId);

            // ── Firma visual: imagen del empleador ──────────────────────────
            // Escalar coordenadas A4 (210×297mm) al tamaño real de la página
            $scaleX = $size['width']  / 210.0;
            $scaleY = $size['height'] / 297.0;

            $fpdi->Image(
                $cfg['img_abs_path'],
                $cfg['x'] * $scaleX,
                $cfg['y'] * $scaleY,
                $cfg['w'] * $scaleX,
                $cfg['h'] * $scaleY,
                $cfg['img_ext']
            );

            // ── Firma criptográfica digital (X.509, PKCS#7 detached) ────────────
            // setSignature() debe llamarse ANTES de Output().
            // Sin setSignatureAppearance() el widget queda embebido sin campo
            // visual en la página (el sello ya está como imagen estampada).
            // cert_type=2: fill+sign permitidos → el empleado puede firmar después.
            if ($cfg['cert_uri'] && $cfg['key_uri']) {
                $fpdi->setSignature(
                    $cfg['cert_uri'],  // 'file:///ruta/al/certificado.crt'
                    $cfg['key_uri'],   // 'file:///ruta/a/la/clave.key'
                    '',                // sin contraseña (clave generada sin cifrar)
                    '',                // sin cadena de certificados adicional
                    2,                 // MDP cert_type=2: fill+sign permitidos
                    $cfg['cert_info']  // metadatos institucionales del firmante
                );
            }

            $content = $fpdi->Output('', 'S');
            unset($fpdi);

            return $content;

        } catch (\Exception $e) {
            Log::warning('ProcessPayslipBatch: fallo al generar PDF firmado; se entrega original.', [
                'archivo' => basename($srcPath),
                'error'   => $e->getMessage(),
            ]);

            return file_get_contents($srcPath);
        }
    }
}
