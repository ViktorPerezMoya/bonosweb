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
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedBigInteger('disagreement_reason_id')->nullable()->after('status');
            $table->string('disconformity_reason')->nullable()->after('disagreement_reason_id');
            
            $table->foreign('disagreement_reason_id')->references('id')->on('disagreement_reasons')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['disagreement_reason_id']);
            $table->dropColumn(['disagreement_reason_id', 'disconformity_reason']);
        });
    }
};
