<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disagreement_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reason_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default reasons for all existing companies
        $companies = DB::table('companies')->pluck('id');
        $defaultReasons = [
            'Otros Motivos',
            'Diferencia en liquidación de haberes',
            'Diferencia en liquidación de retenciones',
            'Diferencia en liquidación de adicionales'
        ];

        foreach ($companies as $companyId) {
            foreach ($defaultReasons as $reason) {
                DB::table('disagreement_reasons')->insert([
                    'company_id' => $companyId,
                    'reason_text' => $reason,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disagreement_reasons');
    }
};
