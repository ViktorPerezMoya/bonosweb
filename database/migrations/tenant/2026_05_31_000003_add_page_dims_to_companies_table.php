<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Dimensiones reales de la página del PDF de muestra (en mm).
            // Usadas por ProcessPayslipBatch para escalar correctamente las
            // coordenadas de firma cuando la orientación no es A4 portrait.
            $table->decimal('signature_page_w', 6, 2)->nullable()->after('signature_anchor_text');
            $table->decimal('signature_page_h', 6, 2)->nullable()->after('signature_page_w');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['signature_page_w', 'signature_page_h']);
        });
    }
};
