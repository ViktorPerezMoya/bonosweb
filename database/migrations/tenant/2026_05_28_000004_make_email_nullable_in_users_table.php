<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hace que el email del usuario sea nullable.
 *
 * Motivo: los empleados que no son Admin/RRHH inician sesión usando su DNI o
 * CUIL, por lo que pueden no tener dirección de correo electrónico. El índice
 * UNIQUE se mantiene: en MySQL los valores NULL no colisionan entre sí en un
 * índice único, por lo que múltiples usuarios sin email coexisten sin problema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Eliminar el índice único existente sobre email
            $table->dropUnique(['email']);

            // 2. Hacer la columna nullable
            $table->string('email')->nullable()->change();

            // 3. Volver a agregar el índice único.
            //    MySQL permite múltiples NULL en un índice UNIQUE.
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
        });
    }
};
