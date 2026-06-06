<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            // Eliminar la restricción unique de CUIL: el mismo empleado puede
            // aparecer en múltiples empresas (un perfil por empresa).
            $table->dropUnique(['cuil']);

            // Clave foránea a la empresa. Nullable para compatibilidad con
            // registros existentes; se podrá asignar en el proceso de migración.
            $table->foreignId('company_id')
                ->nullable()
                ->after('user_id')
                ->constrained('companies')
                ->nullOnDelete();

            // Garantiza que un empleado solo puede tener un perfil por empresa.
            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'company_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->unique('cuil');
        });
    }
};
