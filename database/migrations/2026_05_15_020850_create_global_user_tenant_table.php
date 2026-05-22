<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_user_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['global_user_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_user_tenant');
    }
};
