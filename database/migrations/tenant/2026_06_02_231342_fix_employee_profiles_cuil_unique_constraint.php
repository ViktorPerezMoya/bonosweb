<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            // Eliminar el índice único global
            $table->dropUnique('employee_profiles_cuil_unique');
            // Añadir índice único compuesto (cuil + company_id)
            $table->unique(['cuil', 'company_id'], 'employee_profiles_cuil_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropUnique('employee_profiles_cuil_company_unique');
            $table->unique('cuil', 'employee_profiles_cuil_unique');
        });
    }
};
