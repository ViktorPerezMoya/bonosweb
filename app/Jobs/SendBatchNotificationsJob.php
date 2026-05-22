<?php

namespace App\Jobs;

use App\Models\UploadBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBatchNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Buscar todos los lotes cuya fecha de notificación ya se cumplió y no han sido notificados
        $batchesToNotify = UploadBatch::where('notification_date', '<=', now())
            ->where('notifications_sent', false)
            ->where('status', 'completed')
            ->with('payslips.employee') // Cargar relaciones
            ->get();

        foreach ($batchesToNotify as $batch) {
            
            // Aquí iría la lógica real de envío de emails o notificaciones push (FCM)
            // utilizando la fachada Mail o Notification de Laravel.
            // Ejemplo:
            // foreach($batch->payslips as $payslip) {
            //     Mail::to($payslip->employee->email)->send(new NewPayslipAvailable($payslip));
            // }

            // Para propósitos de este sistema, registramos en el log que se enviaron.
            Log::info("Notificaciones enviadas para el lote ID: {$batch->id} (Periodo: {$batch->period_year}-{$batch->period_month} {$batch->liquidation_type})");

            // Marcar el lote como notificado para que no se vuelva a enviar
            $batch->update([
                'notifications_sent' => true
            ]);
        }
    }
}
