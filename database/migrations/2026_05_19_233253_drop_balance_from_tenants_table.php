<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el campo `balance` de la tabla `tenants`.
     * Este campo fue reemplazado por `current_balance`, que se gestiona
     * automáticamente por el módulo de Cobros y Facturación.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0);
        });
    }
};
