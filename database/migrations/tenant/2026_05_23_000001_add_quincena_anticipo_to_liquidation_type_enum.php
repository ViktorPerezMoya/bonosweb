<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $enum = "'mensual', 'quincena', 'anticipo', 'sac', 'vacaciones', 'gratificacion', 'final', 'retroactivo'";

        DB::statement("ALTER TABLE upload_batches MODIFY COLUMN liquidation_type ENUM({$enum}) NOT NULL DEFAULT 'mensual'");
        DB::statement("ALTER TABLE payslips MODIFY COLUMN liquidation_type ENUM({$enum}) NOT NULL DEFAULT 'mensual'");
    }

    public function down(): void
    {
        $enum = "'mensual', 'sac', 'vacaciones', 'gratificacion', 'final', 'retroactivo'";

        DB::statement("ALTER TABLE upload_batches MODIFY COLUMN liquidation_type ENUM({$enum}) NOT NULL DEFAULT 'mensual'");
        DB::statement("ALTER TABLE payslips MODIFY COLUMN liquidation_type ENUM({$enum}) NOT NULL DEFAULT 'mensual'");
    }
};
