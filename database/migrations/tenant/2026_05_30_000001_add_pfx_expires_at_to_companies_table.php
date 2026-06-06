<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la columna `signature_pfx_expires_at` a la tabla `companies`.
 *
 * Almacena la fecha de vencimiento del certificado .pfx de cada empresa,
 * permitiendo detectar certificados próximos a vencer desde el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('signature_pfx_expires_at')
                  ->nullable()
                  ->after('signature_h')
                  ->comment('Fecha de vencimiento del certificado PFX generado para esta empresa');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('signature_pfx_expires_at');
        });
    }
};
