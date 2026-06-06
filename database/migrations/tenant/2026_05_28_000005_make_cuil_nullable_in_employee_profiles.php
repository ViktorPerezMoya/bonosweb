<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hace que la columna `cuil` de employee_profiles sea nullable.
 *
 * Motivo: al crear un legajo para un empleado puede no tenerse el CUIL al
 * momento del alta (p. ej. empleados importados). El CUIL se completa luego
 * desde el panel de RRHH.
 *
 * El índice UNIQUE se conserva: en MySQL los NULL no colisionan entre sí en
 * un índice único, así que múltiples legajos sin CUIL no generan conflicto.
 * En cambio, dos legajos con el MISMO CUIL no-null siguen siendo rechazados.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar si el índice existe antes de intentar eliminarlo
        // (algunos tenants pueden no tenerlo si fueron creados parcialmente)
        $indexExists = collect(
            \Illuminate\Support\Facades\DB::select(
                "SHOW INDEX FROM employee_profiles WHERE Key_name = 'employee_profiles_cuil_unique'"
            )
        )->isNotEmpty();

        Schema::table('employee_profiles', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropUnique(['cuil']);
            }
            $table->string('cuil')->nullable()->change();
            $table->unique('cuil');
        });
    }

    public function down(): void
    {
        $indexExists = collect(
            \Illuminate\Support\Facades\DB::select(
                "SHOW INDEX FROM employee_profiles WHERE Key_name = 'employee_profiles_cuil_unique'"
            )
        )->isNotEmpty();

        Schema::table('employee_profiles', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropUnique(['cuil']);
            }
            $table->string('cuil')->nullable(false)->change();
            $table->unique('cuil');
        });
    }
};
