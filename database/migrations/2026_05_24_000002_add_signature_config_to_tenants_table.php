<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Coordenadas en mm sobre hoja A4 (210 × 297 mm)
            $table->decimal('signature_x', 6, 2)->nullable()->after('cert_expiry');
            $table->decimal('signature_y', 6, 2)->nullable()->after('signature_x');
            $table->decimal('signature_w', 6, 2)->nullable()->after('signature_y');
            $table->decimal('signature_h', 6, 2)->nullable()->after('signature_w');
            // Paths relativos al disco local del tenant (Storage::disk('local'))
            $table->string('signature_image_path')->nullable()->after('signature_h');
            $table->string('signature_preview_path')->nullable()->after('signature_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'signature_x', 'signature_y', 'signature_w', 'signature_h',
                'signature_image_path', 'signature_preview_path',
            ]);
        });
    }
};
