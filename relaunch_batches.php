<?php
tenancy()->initialize(App\Models\Tenant::find('miempresa'));
$filePath = 'batches/temp/Ran6FjyZknXrQOHS0oBcfitPdySwvgqRHpyjPl8r.pdf';
if (!Storage::disk('local')->exists($filePath)) {
    echo "Archivo NO encontrado: {$filePath}" . PHP_EOL;
} else {
    $batch = App\Models\UploadBatch::find(5);
    $batch->update(['status' => 'pending', 'processed_files' => 0, 'error_log' => null]);
    App\Jobs\ProcessPayslipBatch::dispatch($batch, $filePath);
    echo "DISPATCHED batch 5 con {$filePath}" . PHP_EOL;
}