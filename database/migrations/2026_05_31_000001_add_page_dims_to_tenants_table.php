<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Dimensiones reales de la página del PDF de muestra (en mm).
            // Permite que el canvas del configurador use el aspect-ratio correcto
            // y que el cálculo px↔mm funcione para cualquier orientación.
            $table->decimal('signature_page_w', 6, 2)->nullable()->after('signature_preview_path');
            $table->decimal('signature_page_h', 6, 2)->nullable()->after('signature_page_w');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['signature_page_w', 'signature_page_h']);
        });
    }
};
