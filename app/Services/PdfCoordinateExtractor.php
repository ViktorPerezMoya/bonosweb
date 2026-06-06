<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

/**
 * Extrae las coordenadas (X, Y) de una cadena de texto dentro de un PDF
 * usando la Transformation Matrix (operador Tm) de smalot/pdfparser.
 *
 * Los PDFs almacenan la posición de cada fragmento de texto en una matriz de
 * transformación de 6 componentes: [a, b, c, d, tx, ty].
 *
 *   · tx (índice 4): posición X en unidades del espacio de usuario del PDF.
 *   · ty (índice 5): posición Y en unidades del espacio de usuario del PDF,
 *                    medida desde el borde INFERIOR de la página.
 *
 * La unidad estándar de los PDFs es el punto tipográfico (pt).
 * TCPDF usa milímetros, origen en la esquina SUPERIOR izquierda.
 * La conversión implica dos pasos:
 *   1. pt → mm : multiplicar por PT_TO_MM (0.352778).
 *   2. Y invertida: y_tcpdf = altoPágina_mm − y_mm_desde_abajo.
 *
 * Este servicio devuelve las coordenadas en mm con Y desde abajo (PDF nativo).
 * El Job es responsable de invertir Y usando la altura de página que FPDI
 * provee en mm (ya confiable y exacta).
 */
class PdfCoordinateExtractor
{
    /** 1 punto tipográfico = 0.352778 milímetros */
    private const PT_TO_MM = 0.352778;

    /**
     * Número máximo de entradas Tm adyacentes que se concatenan para buscar
     * la cadena ancla. Cubre textos particionados por el motor de renderizado.
     */
    private const WINDOW_SIZE = 20;

    /**
     * Busca $anchorText en todas las páginas del PDF indicado y retorna las
     * coordenadas en mm del primer fragmento que coincida.
     *
     * @param  string      $pdfPath    Ruta absoluta al archivo PDF.
     * @param  string      $anchorText Texto exacto (o parcial) a buscar.
     * @return array{x_mm: float, y_mm_from_bottom: float, font_size_mm: float}|null
     *         null si el texto no se encontró o si ocurre un error de parseo.
     */
    public function findCoordinates(string $pdfPath, string $anchorText): ?array
    {
        if (empty(trim($anchorText))) {
            return null;
        }

        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($pdfPath);
        } catch (\Exception $e) {
            Log::warning('PdfCoordinateExtractor: no se pudo parsear el PDF.', [
                'path'  => basename($pdfPath),
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        foreach ($pdf->getPages() as $page) {
            $result = $this->searchPage($page, $anchorText);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Busca $anchorText en un objeto Page ya parseado por smalot/pdfparser.
     *
     * Idóneo para búsquedas por-página cuando el PDF completo ya fue parseado
     * con anterioridad (ej: en handleMassivePdf), evitando re-parsear el archivo
     * en cada iteración del bucle y mezclando coordenadas de páginas distintas.
     *
     * @param  Page   $page       Objeto página de smalot/pdfparser.
     * @param  string $anchorText Texto exacto (o parcial) a buscar.
     * @return array{x_mm: float, y_mm_from_bottom: float, font_size_mm: float}|null
     */
    public function findCoordinatesInPage(Page $page, string $anchorText): ?array
    {
        if (empty(trim($anchorText))) {
            return null;
        }

        return $this->searchPage($page, $anchorText);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Busca el texto ancla en una única página.
     *
     * Estrategia en dos pasos:
     *
     *   1. Búsqueda directa: cada entrada Tm se inspecciona individualmente.
     *      Cubre el caso más común donde el texto ancla cae en un solo fragmento.
     *
     *   2. Ventana deslizante: se concatenan hasta WINDOW_SIZE entradas
     *      consecutivas. Resuelve textos que el motor del PDF particionó en
     *      múltiples fragmentos (ej: "Firma " + "del " + "Empleador").
     *      En este caso se retornan las coordenadas del primer fragmento de
     *      la ventana (donde comienza el texto ancla).
     */
    private function searchPage(Page $page, string $anchorText): ?array
    {
        try {
            $dataTm = $page->getDataTm();
        } catch (\Exception $e) {
            // El stream de contenido de esta página no es legible; continuar.
            return null;
        }

        if (empty($dataTm)) {
            return null;
        }

        // Normalizar texto ancla (sin espacios y en minúsculas)
        $normalizedAnchor = preg_replace('/\s+/', '', mb_strtolower($anchorText));

        // ── Paso 1: coincidencia directa en cada fragmento individual ──────────
        foreach ($dataTm as $entry) {
            // getDataTm() siempre retorna [$tm, $text] (y opcionales font id/size).
            $tm   = $entry[0];
            $text = $entry[1] ?? '';

            if ($text !== '') {
                $normalizedText = preg_replace('/\s+/', '', mb_strtolower($text));
                if (str_contains($normalizedText, $normalizedAnchor)) {
                    return $this->tmToMm($tm);
                }
            }
        }

        // ── Paso 2: ventana deslizante para texto fragmentado ─────────────────
        $count = count($dataTm);
        for ($i = 0; $i < $count; $i++) {
            $accumulated = '';
            $limit       = min($i + self::WINDOW_SIZE, $count);

            for ($j = $i; $j < $limit; $j++) {
                $accumulated .= ($dataTm[$j][1] ?? '');

                // En cuanto se forme la cadena ancla, retornar coords del inicio.
                $normalizedAccumulated = preg_replace('/\s+/', '', mb_strtolower($accumulated));
                if (str_contains($normalizedAccumulated, $normalizedAnchor)) {
                    return $this->tmToMm($dataTm[$i][0]);
                }
            }
        }

        return null;
    }

    /**
     * Convierte el array Tm de smalot/pdfparser a coordenadas en milímetros.
     *
     * Tm = [a, b, c, d, tx, ty]  (los valores llegan como strings).
     *   tx = componente de traslación X en puntos.
     *   ty = componente de traslación Y en puntos, origen en borde inferior.
     *
     * @param  array<int, string|float> $tm  Los 6 componentes de la matriz Tm.
     * @return array{x_mm: float, y_mm_from_bottom: float, font_size_mm: float}
     */
    private function tmToMm(array $tm): array
    {
        return [
            'x_mm'             => (float) ($tm[4] ?? 0) * self::PT_TO_MM,
            'y_mm_from_bottom' => (float) ($tm[5] ?? 0) * self::PT_TO_MM,
            'font_size_mm'     => abs((float) ($tm[3] ?? 10)) * self::PT_TO_MM,
        ];
    }
}
