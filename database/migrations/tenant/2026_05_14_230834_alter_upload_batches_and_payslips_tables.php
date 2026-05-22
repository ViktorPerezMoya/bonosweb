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
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->integer('period_year')->after('original_filename')->nullable();
            $table->integer('period_month')->after('period_year')->nullable();
            $table->enum('liquidation_type', ['mensual', 'sac', 'vacaciones', 'gratificacion', 'final', 'retroactivo'])->after('period_month')->default('mensual');
            $table->dateTime('notification_date')->after('error_log')->nullable();
            $table->boolean('notifications_sent')->after('notification_date')->default(false);
        });

        Schema::table('payslips', function (Blueprint $table) {
            // Eliminar la columna 'period' antigua
            $table->dropColumn('period');
            
            $table->integer('period_year')->after('upload_batch_id')->nullable();
            $table->integer('period_month')->after('period_year')->nullable();
            $table->enum('liquidation_type', ['mensual', 'sac', 'vacaciones', 'gratificacion', 'final', 'retroactivo'])->after('period_month')->default('mensual');
            
            $table->boolean('is_rectified')->after('status')->default(false);
            $table->unsignedBigInteger('rectified_by_id')->nullable()->after('is_rectified')->comment('Referencia al nuevo payslip que rectifica a este');
            $table->foreign('rectified_by_id')->references('id')->on('payslips')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['rectified_by_id']);
            $table->dropColumn(['period_year', 'period_month', 'liquidation_type', 'is_rectified', 'rectified_by_id']);
            $table->string('period')->after('upload_batch_id')->nullable();
        });

        Schema::table('upload_batches', function (Blueprint $table) {
            $table->dropColumn(['period_year', 'period_month', 'liquidation_type', 'notification_date', 'notifications_sent']);
        });
    }
};
