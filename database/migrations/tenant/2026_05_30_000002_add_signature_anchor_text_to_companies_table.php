<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega `signature_anchor_text` a la tabla `companies`.
 *
 * Cuando este campo está configurado, el Job ProcessPayslipBatch busca esa
 * cadena en el texto del PDF usando smalot/pdfparser y extrae las coordenadas
 * de la Transformation Matrix (Tm) para posicionar la firma dinámicamente.
 * Si el campo es null o la búsqueda falla, se usan signature_x / signature_y.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('signature_anchor_text', 255)
                  ->nullable()
                  ->after('signature_pfx_expires_at')
                  ->comment('Texto ancla para posicionamiento dinámico de firma. Ej: "Firma del Empleador"');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('signature_anchor_text');
        });
    }
};
