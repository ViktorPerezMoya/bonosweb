<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_users', function (Blueprint $table) {
            $table->id();
            $table->string('cuil')->unique();
            $table->string('dni')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_users');
    }
};
