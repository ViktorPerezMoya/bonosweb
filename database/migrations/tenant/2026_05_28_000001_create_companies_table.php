<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cuit')->unique();
            $table->boolean('is_main')->default(false);

            // Firma digital
            $table->string('signature_pfx_path')->nullable();
            $table->text('signature_pfx_password')->nullable();
            $table->float('signature_x')->nullable();
            $table->float('signature_y')->nullable();
            $table->float('signature_w')->nullable();
            $table->float('signature_h')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
