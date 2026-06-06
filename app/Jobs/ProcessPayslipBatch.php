<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\UploadBatch;
use App\Models\Payslip;
use App\Models\EmployeeProfile;
use App\Models\Scopes\CurrentCompanyScope;
use App\Services\PdfCoordinateExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Pdf\CustomFpdi;
use Smalot\PdfParser\Parser;
use ZipArchive;
use Exception;

class ProcessPayslipBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;
    protected $filePath;
    protected int $companyId;
    protected array|null $sigConfig = null;

    public $timeout = 3600;

    public function __construct(UploadBatch $batch, string $filePath, int $companyId)
    {
        $this->batch     = $batch;
        $this->filePath  = $filePath;
        $this->companyId = $companyId;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): void
    {
        // Carga el modelo Company explícito (sin session/scope) y configura la firma
        $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->findOrFail($this->companyId);

        $this->loadSignatureConfig($company);

        $this->batch->update(['status' => 'processing']);

        if ($this->batch->file_type === 'pdf') {
            $this->handleMassivePdf($company);
        } else {
            $this->handleZip($company);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estrategia ZIP (lógica original preservada íntegramente)
    // ─────────────────────────────────────────────────────────────────────────

    protected function handleZip(Company $company): void
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

        $employerCuit = $this->getEmployerCuit($company);
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

    protected function handleMassivePdf(Company $company): void
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

        $employerCuit = $this->getEmployerCuit($company);
        $errors       = [];
        $processed    = 0;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            try {
                $pageText = $pages[$pageNo - 1]->getText();

                // Liberar el objeto Page de memoria tan pronto como sea posible.
                // En PDFs masivos de cientos de páginas esto evita agotar la RAM del worker.
                // Nota: la búsqueda de ancla de texto se realiza después sobre el PDF
                // individual ya extraído (coordenadas consistentes con el CTM de FPDI).
                unset($pages[$pageNo - 1]);

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
            $fpdi = new CustomFpdi();
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

    protected function getEmployerCuit(Company $company): string
    {
        return preg_replace('/\D/', '', $company->cuit ?? '');
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
        // El scope de empresa activa no aplica en jobs (no hay sesión HTTP).
        // Filtramos explícitamente por company_id para encontrar el perfil correcto.
        // Defensive: si $this->companyId es 0 (null coercionado), usar el company_id del batch.
        $cid = $this->companyId ?: ($this->batch->company_id ?? null);
        return EmployeeProfile::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $cid)
            ->where(fn ($q) => $q->where('cuil', $cuil)->orWhere('cuil', $rawCuil))
            ->first();
    }

    /**
     * Calcula SHA-256, copia el archivo a su ruta final e inserta el Payslip.
     * Aplica rectificativa inmutable: el recibo previo se marca, no se borra.
     */
    protected function persistPayslip(EmployeeProfile $profile, string $absoluteSrcPath, string $originalName): bool
    {
        // Estampar firma visual del empleador (si está configurada).
        $finalContent = $this->stampedPdfContent($absoluteSrcPath);
        $fileHash     = hash('sha256', $finalContent);   // hash del archivo FINAL
        $periodYear   = $this->batch->period_year;
        $periodMonth  = str_pad($this->batch->period_month, 2, '0', STR_PAD_LEFT);
        $finalPath    = "payslips/{$periodYear}-{$periodMonth}/{$fileHash}.pdf";

        Storage::disk('local')->put($finalPath, $finalContent);
        unset($finalContent); // liberar memoria

        // Defensive fallback: si $this->companyId es 0/null (worker viejo o error de serialización),
        // usar el company_id del batch como respaldo.
        $cid = $this->companyId ?: ($this->batch->company_id ?? null);

        $existing = Payslip::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('employee_id', $profile->user_id)
            ->where('company_id', $cid)
            ->where('period_year', $this->batch->period_year)
            ->where('period_month', $this->batch->period_month)
            ->where('liquidation_type', $this->batch->liquidation_type)
            ->where('is_rectified', false)
            ->first();

        $newPayslip = Payslip::create([
            'employee_id'       => $profile->user_id,
            'company_id'        => $cid,
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
    // Firma digital con certificado PFX de la Company
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Carga la configuración de firma desde el modelo Company.
     *
     * El campo signature_pfx_path apunta a un archivo PKCS#12 (.pfx/.p12) en el
     * disco local del tenant. Se usa openssl_pkcs12_read() para extraer el
     * certificado y la clave privada en memoria, evitando archivos temporales.
     */
    protected function loadSignatureConfig(Company $company): void
    {
        if ($company->signature_x === null) {
            return;
        }

        $this->sigConfig = [
            'cert_pem'    => null,
            'key_pem'     => null,
            'x'           => (float) $company->signature_x,
            'y'           => (float) $company->signature_y,
            'w'           => (float) $company->signature_w,
            'h'           => (float) $company->signature_h,
            // Dimensiones de página de referencia (mm): se usan para escalar
            // correctamente las coordenadas al tamaño real de cada recibo.
            // Si son null, se asume A4 portrait (210×297 mm) por compatibilidad.
            'page_w'      => (float) ($company->signature_page_w ?? 210.0),
            'page_h'      => (float) ($company->signature_page_h ?? 297.0),
            // Campo ancla: si está configurado el Job intentará posicionar
            // la firma dinámicamente. Si falla, usa x/y estáticos como fallback.
            'anchor_text' => $company->signature_anchor_text,
            'image_path'  => $company->signature_image_path,
            'cert_info'   => [
                'Name'        => $company->name,
                'Location'    => 'Argentina',
                'Reason'      => 'Emisión de recibo de haberes original',
                'ContactInfo' => preg_replace('/\D/', '', $company->cuit ?? ''),
            ],
        ];

        if ($company->signature_pfx_path) {
            $pfxAbsPath = Storage::disk('local')->path($company->signature_pfx_path);

            if (!file_exists($pfxAbsPath)) {
                Log::warning('ProcessPayslipBatch: PFX de firma no encontrado.', [
                    'company_id' => $company->id,
                    'path'       => $pfxAbsPath,
                ]);
            } else {
                $pfxContent = file_get_contents($pfxAbsPath);
                $certs      = [];

                $password = $company->signature_pfx_password ?? '';
                if (!empty($password)) {
                    try {
                        $password = decrypt($password);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                        // Fallback a texto plano si no está encriptada
                    }
                }

                if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                    Log::warning('ProcessPayslipBatch: no se pudo parsear el PFX (contraseña incorrecta o archivo inválido).', [
                        'company_id' => $company->id,
                    ]);
                } else {
                    $this->sigConfig['cert_pem'] = $certs['cert'];
                    $this->sigConfig['key_pem']  = $certs['pkey'];
                }
            }
        }
    }

    /**
     * Retorna el contenido del PDF con:
     *  - Firma criptográfica X.509 extraída del PFX de la Company (PKCS#7).
     *  - Widget de firma visible en las coordenadas configuradas en la Company.
     *
     * cert_type=2: permite que el empleado agregue su propia firma electrónica
     * posteriormente sin invalidar el sello del empleador.
     *
     * Si la firma no está configurada o FPDI no soporta el PDF (compressed
     * streams / PDF 1.5+), retorna el PDF original sin modificaciones.
     */
    protected function stampedPdfContent(string $srcPath): string
    {
        if (!$this->sigConfig) {
            return file_get_contents($srcPath);
        }

        $cfg = $this->sigConfig;

        try {
            $fpdi = new CustomFpdi();
            $fpdi->SetAutoPageBreak(false);
            $fpdi->setSourceFile($srcPath);

            $templateId = $fpdi->importPage(1);
            $size       = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage(
                $size['orientation'] ?? 'P',
                [$size['width'], $size['height']]
            );
            $fpdi->useTemplate($templateId);

            // ── Escalar coordenadas al tamaño real de la página ────────────────
            // Usa las dimensiones de referencia con las que se configuró la
            // firma (detección automática al subir el PDF de muestra).
            $refW = $cfg['page_w'] ?: 210.0;
            $refH = $cfg['page_h'] ?: 297.0;
            $scaleX = $size['width']  / $refW;
            $scaleY = $size['height'] / $refH;

            // ── Coordenadas base (estáticas configuradas en la Company) ──────────
            // Según requerimiento, asumimos que ya vienen en milímetros desde la BD
            // y no deben invertirse ni convertirse de nuevo.
            $sigX = $cfg['x'];
            $sigY = $cfg['y'];
            $sigW = $cfg['w'];
            $sigH = $cfg['h'];

            $methodUsed = 'Fallback';

            // ── Anclaje dinámico: override de X/Y si se encontró el texto ancla ──
            //
            // Se busca en $srcPath (PDF individual de una sola página: ya sea el archivo
            // extraído por extractSinglePage o el PDF individual del ZIP). Esto garantiza
            // que las coordenadas Tm de smalot sean consistentes con el CTM que FPDI aplicó
            // al importar la página con useTemplate().
            //
            // PdfCoordinateExtractor devuelve Y desde borde INFERIOR en mm.
            // La fórmula solicitada es: Y_tcpdf = (PageHeight - Y_parser_text) - AltoDeLaFirma

            if (!empty($cfg['anchor_text'])) {
                try {
                    $anchored = app(PdfCoordinateExtractor::class)
                        ->findCoordinates($srcPath, $cfg['anchor_text']);

                    if ($anchored !== null) {
                        $methodUsed = 'TextAnchor';
                        
                        $pageHeightMm   = $size['height'];
                        
                        // Mantenemos el X estático configurado en la BD.
                        // El ancla solo se usa para calcular la "coordenada Y exacta" según el largo,
                        // evitando el bug donde el parser reporta X=1.0 si el ancla es parte de un bloque largo.
                        $sigY = $pageHeightMm - $anchored['y_mm_from_bottom'] - $sigH - ($anchored['font_size_mm'] * 25);

                        Log::debug('ProcessPayslipBatch: firma posicionada por ancla de texto.', [
                            'archivo' => basename($srcPath),
                            'anchor'  => $cfg['anchor_text'],
                            'x_mm'    => round($sigX, 2),
                            'y_mm'    => round($sigY, 2),
                        ]);
                    } else {
                        Log::warning('ProcessPayslipBatch: el texto ancla no se encontro en la pagina.', [
                            'archivo' => basename($srcPath),
                            'anchor'  => $cfg['anchor_text'],
                        ]);
                    }
                } catch (\Throwable $anchorEx) {
                    // Capturar Throwable (no sólo Exception): smalot/pdfparser puede lanzar
                    // Error/TypeError en PDFs con fuentes o encoding no soportados.
                    // $sigX/$sigY conservan las coordenadas estáticas como fallback.
                    Log::warning('ProcessPayslipBatch: ancla de texto falló; usando coordenadas estáticas.', [
                        'archivo' => basename($srcPath),
                        'anchor'  => $cfg['anchor_text'],
                        'error'   => $anchorEx->getMessage(),
                        'clase'   => get_class($anchorEx),
                    ]);
                }
            }

            // ── Telemetría y Logging (Crucial para el debug) ──────────────────────
            Log::channel('single')->debug('ProcessPayslipBatch: Coordenadas Inyectadas', [
                'pageNo'      => 1, // srcPath es de una única página
                'orientation' => $size['orientation'] ?? 'P',
                'width_mm'    => round($size['width'], 2),
                'height_mm'   => round($size['height'], 2),
                'method'      => $methodUsed,
                'anchor_text' => $cfg['anchor_text'],
                'final_x_mm'  => round($sigX, 2),
                'final_y_mm'  => round($sigY, 2),
                'final_w_mm'  => round($sigW, 2),
                'final_h_mm'  => round($sigH, 2),
            ]);

            // ── Firma criptográfica digital (X.509, PKCS#7 detached) ────────────
            // Usa cert y key en formato PEM extraídos en memoria del PFX.
            // cert_type=2: fill+sign — el empleado puede firmar después.
            if (!empty($cfg['cert_pem']) && !empty($cfg['key_pem'])) {
                $fpdi->setSignature(
                    $cfg['cert_pem'],
                    $cfg['key_pem'],
                    '',   // sin contraseña: la clave ya fue extraída del PFX
                    '',   // sin cadena de certificados adicional
                    2,
                    $cfg['cert_info']
                );
            }

            // ── Widget de firma visible en coordenadas resueltas ─────────────────
            // $sigX/$sigY ya incorporan el override del texto ancla (si aplica).
            // En caso de fallo del extractor, contienen las coords estáticas.
            $fpdi->setSignatureAppearance($sigX, $sigY, $sigW, $sigH);

            // ── Inyectar imagen visual de la firma (PNG/JPG) ─────────────────────
            // Para que la firma sea visible en navegadores (Chrome, Edge, móviles)
            if (!empty($cfg['image_path'])) {
                $imageAbsPath = Storage::disk('local')->path($cfg['image_path']);
                if (file_exists($imageAbsPath)) {
                    // TCPDF Image() dibuja directamente sobre el lienzo actual
                    $fpdi->Image($imageAbsPath, $sigX, $sigY, $sigW, $sigH);
                }
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
