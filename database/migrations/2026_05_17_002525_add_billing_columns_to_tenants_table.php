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
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('service_base_amount', 10, 2)->default(0);
            $table->integer('payment_day')->default(15);
            $table->boolean('apply_inflation')->default(false);
            $table->decimal('current_balance', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['service_base_amount', 'payment_day', 'apply_inflation', 'current_balance']);
        });
    }
};
