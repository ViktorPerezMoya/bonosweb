<?php

namespace App\Livewire\Tenant;

use App\Models\Company;
use App\Models\Tenant;
use App\Services\CompanyContextService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Pdf\CustomFpdi;
use App\Services\PdfCoordinateExtractor;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi;

class SignatureConfigurator extends Component
{
    use WithFileUploads;

    // ── Uploads ───────────────────────────────────────────────────────────────
    public $samplePdf;
    public $signatureImage;

    // ── Estado ────────────────────────────────────────────────────────────────
    public bool   $previewAvailable   = false;
    public bool   $signatureAvailable = false;
    public bool   $coordinatesSaved   = false;
    public string $uploadError        = '';

    // ── Coordenadas guardadas (mm, página real) ───────────────────────────────
    // Siempre son floats; 0.0 = esquina superior-izquierda (nuevo tenant).
    public float $sigXmm = 0.0;
    public float $sigYmm = 0.0;
    public float $sigWmm = 40.0;
    public float $sigHmm = 20.0;

    // ── Indica si el tenant ya ha arrastrado y guardado coordenadas ───────────
    public bool $sigConfigured = false;

    // ── Texto ancla para posicionamiento dinámico ─────────────────────────────
    public string $anchorText = '';
    public float $anchorOffsetY = 10.0;

    // ── Dimensiones reales de la página (mm) ─────────────────────────────────
    // Detectadas al subir el PDF de muestra; defecto A4 portrait.
    public float $pageWmm = 210.0;
    public float $pageHmm = 297.0;

    // ── Dimensiones A4 en mm (constantes de fallback) ─────────────────────────
    const PAGE_W_MM = 210.0;
    const PAGE_H_MM = 297.0;

    // ── Validaciones ──────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'samplePdf'      => 'required|file|mimes:pdf|max:20480',
            'signatureImage' => 'required|file|mimes:png,jpg,jpeg|max:5120',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        abort_if(auth()->user()->role !== 'admin', 403);

        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
        $company = Company::find($companyId);

        if (!$company) {
            abort(404, 'Empresa activa no encontrada');
        }

        $this->sigXmm       = $company->signature_x !== null ? (float) $company->signature_x : 0.0;
        $this->sigYmm       = $company->signature_y !== null ? (float) $company->signature_y : 0.0;
        $this->sigWmm       = $company->signature_w !== null ? (float) $company->signature_w : 40.0;
        $this->sigHmm       = $company->signature_h !== null ? (float) $company->signature_h : 20.0;
        $this->sigConfigured = $company->signature_x !== null;

        $this->anchorText = $company->signature_anchor_text ?? '';
        $this->anchorOffsetY = $company->signature_anchor_offset_y ?? 10.0;

        // Cargar dimensiones de página guardadas (fallback: A4 portrait)
        $this->pageWmm = $company->signature_page_w ? (float) $company->signature_page_w : 210.0;
        $this->pageHmm = $company->signature_page_h ? (float) $company->signature_page_h : 297.0;

        $this->previewAvailable = !empty($company->signature_preview_path)
            && Storage::disk('local')->exists($company->signature_preview_path);

        $this->signatureAvailable = !empty($company->signature_image_path)
            && Storage::disk('local')->exists($company->signature_image_path);
    }

    // ── Upload: PDF de muestra ────────────────────────────────────────────────

    public function uploadSamplePdf(): void
    {
        $this->validateOnly('samplePdf');
        $this->uploadError = '';

        try {
            Storage::disk('local')->makeDirectory('signatures');

            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = Company::find($companyId);

            $pdfRelPath = "signatures/company_{$companyId}_sample_preview.pdf";

            if (Storage::disk('local')->exists($pdfRelPath)) {
                Storage::disk('local')->delete($pdfRelPath);
            }

            $this->samplePdf->storeAs('signatures', "company_{$companyId}_sample_preview.pdf", 'local');

            $company->signature_preview_path = $pdfRelPath;

            // ── Detectar orientación/dimensiones de página con FPDI ──────────
            try {
                $pdfAbsPath = Storage::disk('local')->path($pdfRelPath);
                $fpdi = new Fpdi();
                $fpdi->setSourceFile($pdfAbsPath);
                $tplId = $fpdi->importPage(1);
                $size  = $fpdi->getTemplateSize($tplId);
                unset($fpdi);

                $pageW = round((float) $size['width'],  2);
                $pageH = round((float) $size['height'], 2);

                $company->signature_page_w = $pageW;
                $company->signature_page_h = $pageH;

                $this->pageWmm = $pageW;
                $this->pageHmm = $pageH;
            } catch (\Throwable) {
                // Si FPDI no puede leer el PDF, conservar las dimensiones previas.
            }

            $company->save();

            $this->previewAvailable = true;
            $this->dispatch('preview-ready', pageW: $this->pageWmm, pageH: $this->pageHmm);

        } catch (\Exception $e) {
            $this->uploadError = 'Error al guardar el PDF: ' . $e->getMessage();
        }

        $this->reset('samplePdf');
    }

    // ── Upload: imagen de firma del empleador ─────────────────────────────────

    public function uploadSignatureImage(): void
    {
        $this->validateOnly('signatureImage');
        $this->uploadError = '';

        try {
            Storage::disk('local')->makeDirectory('signatures');
            
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = Company::find($companyId);

            $ext  = strtolower($this->signatureImage->getClientOriginalExtension());
            $filename = "company_{$companyId}_employer_signature.{$ext}";
            $path = $this->signatureImage->storeAs('signatures', $filename, 'local');

            $company->signature_image_path = $path;
            $company->save();

            $this->signatureAvailable = true;
            $this->dispatch('signature-image-ready');

        } catch (\Exception $e) {
            $this->uploadError = 'Error al subir la imagen de firma: ' . $e->getMessage();
        }

        $this->reset('signatureImage');
    }

    // ── Guardar texto ancla ────────────────────────────────────────────────────

    public function saveAnchorText(): void
    {
        $this->validate([
            'anchorText' => 'nullable|string|max:255',
            'anchorOffsetY' => 'required|numeric|min:0|max:100',
        ]);

        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
        $company = Company::find($companyId);

        if (!$company) {
            return;
        }

        $company->signature_anchor_text = trim($this->anchorText) ?: null;
        $company->signature_anchor_offset_y = $this->anchorOffsetY;
        $company->save();

        $this->anchorText = $company->signature_anchor_text ?? '';
        $this->anchorOffsetY = $company->signature_anchor_offset_y ?? 10.0;
        
        session()->flash('message', 'Ajuste de ancla guardado correctamente.');
    }

    // ── Previsualizar Ajuste (Preview Modal) ───────────────────────────────────

    public function generatePreview()
    {
        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
        $company = Company::find($companyId);

        if (!$company || empty($company->signature_preview_path) || empty($company->signature_image_path)) {
            session()->flash('error', 'Falta el PDF de muestra o la imagen de firma.');
            return;
        }

        if (empty($this->anchorText)) {
            session()->flash('error', 'Debes configurar un texto ancla primero.');
            return;
        }

        $srcPath = Storage::disk('local')->path($company->signature_preview_path);
        $sigImagePath = Storage::disk('local')->path($company->signature_image_path);

        if (!file_exists($srcPath) || !file_exists($sigImagePath)) {
            session()->flash('error', 'No se encuentran los archivos en el servidor.');
            return;
        }

        try {
            $anchored = app(PdfCoordinateExtractor::class)->findCoordinates($srcPath, $this->anchorText);

            if (!$anchored) {
                session()->flash('error', 'Texto ancla no encontrado en el PDF.');
                return;
            }

            $fpdi = new CustomFpdi();
            $fpdi->setSourceFile($srcPath);
            $tplId = $fpdi->importPage(1);
            $size = $fpdi->getTemplateSize($tplId);

            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $fpdi->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

            $sigX = $company->signature_x ?? 0.0;
            $sigW = $company->signature_w ?? 40.0;
            $sigH = $company->signature_h ?? 20.0;
            $sigY = $size['height'] - $anchored['y_mm_from_bottom'] - $sigH - $this->anchorOffsetY;
            
            $ext = strtolower(pathinfo($sigImagePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                $ext = 'png';
            }

            $fpdi->Image($sigImagePath, $sigX, $sigY, $sigW, $sigH, strtoupper($ext));

            Log::info('Preview generado', [
                'y_mm_from_bottom' => $anchored['y_mm_from_bottom'],
                'offsetY' => $this->anchorOffsetY,
                'x' => $sigX,
                'y' => $sigY,
                'w' => $sigW,
                'h' => $sigH,
            ]);

            $pdfContent = $fpdi->Output('', 'S');
            $base64 = base64_encode($pdfContent);

            $this->dispatch('preview-generated', data: 'data:application/pdf;base64,' . $base64);

        } catch (\Exception $e) {
            Log::error("Error al generar preview de firma: " . $e->getMessage());
            session()->flash('error', 'Error al procesar el PDF: ' . $e->getMessage());
        }
    }

    // ── Guardar coordenadas desde AlpineJS ────────────────────────────────────

    /**
     * Recibe posición (px) del recuadro y dimensiones del contenedor del frontend.
     * Convierte a mm (A4) y guarda en la tabla tenants.
     */
    public function saveCoordinates(
        float $x, float $y,
        float $w, float $h,
        float $cw, float $ch
    ): void {
        if ($cw <= 0 || $ch <= 0) return;

        // Regla de tres: píxeles de pantalla → milímetros reales de la página
        $pW = $this->pageWmm > 0 ? $this->pageWmm : self::PAGE_W_MM;
        $pH = $this->pageHmm > 0 ? $this->pageHmm : self::PAGE_H_MM;

        $wMm = ($w / $cw) * $pW;
        $hMm = ($h / $ch) * $pH;
        $xMm = ($x / $cw) * $pW;
        $yMm = ($y / $ch) * $pH;

        // Clamp: el recuadro no puede salir del área de la hoja
        $wMm = max(5.0,  min($wMm, $pW));
        $hMm = max(5.0,  min($hMm, $pH));
        $xMm = max(0.0,  min($xMm, $pW - $wMm));
        $yMm = max(0.0,  min($yMm, $pH - $hMm));

        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
        $company = Company::find($companyId);
        
        if ($company) {
            $company->signature_x = round($xMm, 2);
            $company->signature_y = round($yMm, 2);
            $company->signature_w = round($wMm, 2);
            $company->signature_h = round($hMm, 2);
            $company->save();

            $this->sigXmm         = $company->signature_x;
            $this->sigYmm         = $company->signature_y;
            $this->sigWmm         = $company->signature_w;
            $this->sigHmm         = $company->signature_h;
            $this->coordinatesSaved = true;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render(): mixed
    {
        return view('livewire.tenant.signature-configurator')
            ->layout('components.layouts.app', [
                'header' => 'Configurar Firma del Empleador',
                'title'  => 'Firma - BonosWeb',
            ]);
    }
}
