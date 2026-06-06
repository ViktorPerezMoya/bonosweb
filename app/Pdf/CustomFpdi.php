<?php

namespace App\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Extensión de FPDI/TCPDF con cabecera y pie completamente suprimidos.
 *
 * TCPDF dibuja por defecto una línea horizontal en Header() en cada página
 * nueva. Al sobrescribir ambos métodos con cuerpos vacíos el lienzo queda
 * limpio, preservando intacto el diseño original del PDF importado.
 *
 * Este es también el lugar centralizado para inyectar comportamiento global
 * sobre todos los PDFs generados por BonosWeb; por ejemplo:
 *   - Marca de agua (watermark) corporativa.
 *   - Número de página personalizado.
 *   - Sello "COPIA" / "DUPLICADO" condicional.
 */
class CustomFpdi extends Fpdi
{
    /** Suprime la cabecera predeterminada de TCPDF (línea negra + texto). */
    public function Header(): void {}

    /** Suprime el pie de página predeterminado de TCPDF (numeración + línea). */
    public function Footer(): void {}
}
