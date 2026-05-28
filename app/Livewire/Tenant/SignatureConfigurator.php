<?php

namespace App\Livewire\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    // ── Coordenadas guardadas (mm, A4) ────────────────────────────────────────
    public float|null $sigXmm = null;
    public float|null $sigYmm = null;
    public float      $sigWmm = 50.0;
    public float      $sigHmm = 20.0;

    // ── Dimensiones A4 en mm ──────────────────────────────────────────────────
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
        $t = tenant();

        $this->sigXmm = $t->signature_x !== null ? (float) $t->signature_x : null;
        $this->sigYmm = $t->signature_y !== null ? (float) $t->signature_y : null;
        $this->sigWmm = $t->signature_w !== null ? (float) $t->signature_w : 50.0;
        $this->sigHmm = $t->signature_h !== null ? (float) $t->signature_h : 20.0;

        $this->previewAvailable = !empty($t->signature_preview_path)
            && Storage::disk('local')->exists($t->signature_preview_path);

        $this->signatureAvailable = !empty($t->signature_image_path)
            && Storage::disk('local')->exists($t->signature_image_path);
    }

    // ── Upload: PDF de muestra ────────────────────────────────────────────────

    public function uploadSamplePdf(): void
    {
        $this->validateOnly('samplePdf');
        $this->uploadError = '';

        try {
            Storage::disk('local')->makeDirectory('signatures');

            $pdfRelPath = 'signatures/sample_preview.pdf';

            if (Storage::disk('local')->exists($pdfRelPath)) {
                Storage::disk('local')->delete($pdfRelPath);
            }

            $this->samplePdf->storeAs('signatures', 'sample_preview.pdf', 'local');

            $tenant = Tenant::find(tenant('id'));
            $tenant->signature_preview_path = $pdfRelPath;
            $tenant->save();

            $this->previewAvailable = true;
            $this->dispatch('preview-ready');

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
            $ext  = strtolower($this->signatureImage->getClientOriginalExtension());
            $path = $this->signatureImage->storeAs('signatures', 'employer_signature.' . $ext, 'local');

            $tenant = Tenant::find(tenant('id'));
            $tenant->signature_image_path = $path;
            $tenant->save();

            $this->signatureAvailable = true;
            $this->dispatch('signature-image-ready');

        } catch (\Exception $e) {
            $this->uploadError = 'Error al subir la imagen de firma: ' . $e->getMessage();
        }

        $this->reset('signatureImage');
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

        // Regla de tres: píxeles de pantalla → milímetros A4
        $wMm = ($w / $cw) * self::PAGE_W_MM;
        $hMm = ($h / $ch) * self::PAGE_H_MM;
        $xMm = ($x / $cw) * self::PAGE_W_MM;
        $yMm = ($y / $ch) * self::PAGE_H_MM;

        // Clamp: el recuadro no puede salir del área de la hoja
        $wMm = max(5.0,  min($wMm, self::PAGE_W_MM));
        $hMm = max(5.0,  min($hMm, self::PAGE_H_MM));
        $xMm = max(0.0,  min($xMm, self::PAGE_W_MM - $wMm));
        $yMm = max(0.0,  min($yMm, self::PAGE_H_MM - $hMm));

        $tenant = Tenant::find(tenant('id'));
        $tenant->signature_x = round($xMm, 2);
        $tenant->signature_y = round($yMm, 2);
        $tenant->signature_w = round($wMm, 2);
        $tenant->signature_h = round($hMm, 2);
        $tenant->save();

        $this->sigXmm         = $tenant->signature_x;
        $this->sigYmm         = $tenant->signature_y;
        $this->sigWmm         = $tenant->signature_w;
        $this->sigHmm         = $tenant->signature_h;
        $this->coordinatesSaved = true;
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
