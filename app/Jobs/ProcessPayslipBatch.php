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

                $anchored = null;
                if (!empty($this->sigConfig['anchor_text'])) {
                    $anchored = app(PdfCoordinateExtractor::class)
                        ->findCoordinates($fullPath, $this->sigConfig['anchor_text']);
                }

                $processed += (int) $this->persistPayslip($profile, $fullPath, basename($file), $anchored);

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

        $cuilMap = [];
        $pageAnchors = [];
        
        $lastCuil = null;
        $lastProfile = null;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            try {
                $pageText = $pages[$pageNo - 1]->getText();

                $anchored = null;
                if (!empty($this->sigConfig['anchor_text'])) {
                    $anchored = app(PdfCoordinateExtractor::class)
                        ->findCoordinatesInPage($pages[$pageNo - 1], $this->sigConfig['anchor_text']);
                }
                $pageAnchors[$pageNo] = $anchored;

                unset($pages[$pageNo - 1]);

                [$cuil, $rawCuil] = $this->extractEmployeeCuil($pageText, $employerCuit);
                $profile = null;

                if ($cuil) {
                    $profile = $this->findEmployeeProfile($cuil, $rawCuil);
                }

                if (!$cuil || !$profile) {
                    if ($lastCuil && $lastProfile) {
                        $cuil = $lastCuil;
                        $profile = $lastProfile;
                        // Agrupado por continuidad (hereda el empleado de la página anterior)
                    } else {
                        $errors[] = "Pagina {$pageNo}: no se encontro CUIL de empleado y no hay empleado previo.";
                        continue;
                    }
                } else {
                    $lastCuil = $cuil;
                    $lastProfile = $profile;
                }

                if (!isset($cuilMap[$cuil])) {
                    $cuilMap[$cuil] = [
                        'profile' => $profile,
                        'pages'   => []
                    ];
                }
                $cuilMap[$cuil]['pages'][] = $pageNo;

            } catch (Exception $e) {
                $errors[] = "Pagina {$pageNo}: error critico - " . $e->getMessage();
            }
        }

        foreach ($cuilMap as $cuil => $data) {
            $profile = $data['profile'];
            $cuilPages = $data['pages'];
            
            try {
                $fpdi = new CustomFpdi();
                $fpdi->setSourceFile($fullPdfPath);

                foreach ($cuilPages as $pNo) {
                    $templateId = $fpdi->importPage($pNo);
                    $size       = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage(
                        $size['orientation'] ?? 'P',
                        [$size['width'], $size['height']]
                    );
                    $fpdi->useTemplate($templateId);
                }

                $relativePath = $extractDir . '/cuil_' . $cuil . '_' . uniqid() . '.pdf';
                $absolutePagePath = Storage::disk('local')->path($relativePath);
                $fpdi->Output($absolutePagePath, 'F');
                unset($fpdi);

                $groupedAnchors = [];
                foreach ($cuilPages as $idx => $pNo) {
                    $groupedAnchors[$idx + 1] = $pageAnchors[$pNo];
                }

                $originalName = 'recibo_' . $cuil . '_'.date('YmdHis').'.pdf';

                $processed += (int) $this->persistPayslip($profile, $absolutePagePath, $originalName, $groupedAnchors);

                Storage::disk('local')->delete($relativePath);

            } catch (Exception $e) {
                $errors[] = "Error procesando CUIL {$cuil}: " . $e->getMessage();
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
    protected function persistPayslip(EmployeeProfile $profile, string $absoluteSrcPath, string $originalName, ?array $anchoreds = null): bool
    {
        // Estampar firma visual del empleador (si está configurada).
        $finalContent = $this->stampedPdfContent($absoluteSrcPath, $anchoreds);
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
            'page_w'          => (float) ($company->signature_page_w ?? 210.0),
            'page_h'          => (float) ($company->signature_page_h ?? 297.0),
            'anchor_text'     => $company->signature_anchor_text,
            'anchor_offset_y' => (float) ($company->signature_anchor_offset_y ?? 10.0),
            'page_placement'  => $company->signature_page_placement ?? 'all',
            'image_path'      => $company->signature_image_path,
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
    protected function stampedPdfContent(string $srcPath, ?array $anchoreds = null): string
    {
        if (!$this->sigConfig) {
            return file_get_contents($srcPath);
        }

        $cfg = $this->sigConfig;

        try {
            $fpdi = new CustomFpdi();
            $fpdi->SetAutoPageBreak(false);
            $pageCount = $fpdi->setSourceFile($srcPath);

            $placement = $cfg['page_placement'] ?? 'all';
            $sigX = $cfg['x'];
            $sigY = $cfg['y'];
            $sigW = $cfg['w'];
            $sigH = $cfg['h'];

            // ── Firma criptográfica digital (X.509, PKCS#7 detached) ────────────
            if (!empty($cfg['cert_pem']) && !empty($cfg['key_pem'])) {
                $fpdi->setSignature(
                    $cfg['cert_pem'],
                    $cfg['key_pem'],
                    '',   // sin contraseña
                    '',   // sin cadena de certificados adicional
                    3,    // cert_type
                    $cfg['cert_info'],
                    'A'   // <--- MODO APROBACIÓN (Desactiva el DocMDP estricto)
                );
            }

            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $fpdi->importPage($i);
                $size       = $fpdi->getTemplateSize($templateId);

                $fpdi->AddPage(
                    $size['orientation'] ?? 'P',
                    [$size['width'], $size['height']]
                );
                $fpdi->useTemplate($templateId);

                $shouldStamp = false;
                if ($placement === 'all') { $shouldStamp = true; }
                elseif ($placement === 'first' && $i === 1) { $shouldStamp = true; }
                elseif ($placement === 'last' && $i === $pageCount) { $shouldStamp = true; }

                if ($shouldStamp) {
                    $pageSigX = $sigX;
                    $pageSigY = $sigY;
                    $methodUsed = 'Fallback';

                    // ── Anclaje dinámico: override de X/Y si se encontró el texto ancla ──
                    if (!empty($cfg['anchor_text'])) {
                        // El array $anchoreds puede venir indexado por página (1-based) o un solo elemento
                        $anchored = $anchoreds[$i] ?? (isset($anchoreds['x_mm']) ? $anchoreds : null);

                        if ($anchored !== null) {
                            $methodUsed = 'TextAnchor';
                            $pageHeightMm   = $size['height'];
                            $offsetY = $cfg['anchor_offset_y'] ?? 10.0;
                            $pageSigY = $pageHeightMm - $anchored['y_mm_from_bottom'] - $sigH - $offsetY;

                            if ($anchored['x_mm'] > 5.0) {
                                $pageSigX = $anchored['x_mm'] + 15 - ($sigW / 2);
                            }
                        }
                    }

                    // ── Inyectar imagen visual de la firma (PNG/JPG) ─────────────────────
                    if (!empty($cfg['image_path'])) {
                        $imageAbsPath = Storage::disk('local')->path($cfg['image_path']);
                        if (file_exists($imageAbsPath)) {
                            $fpdi->Image($imageAbsPath, $pageSigX, $pageSigY, $sigW, $sigH);
                        }
                    }

                    // Registrar coordenadas para la primera página donde estampe
                    if ($shouldStamp && ($i === 1 || $placement === 'last')) {
                        $fpdi->setSignatureAppearance($pageSigX, $pageSigY, $sigW, $sigH);
                    }
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
