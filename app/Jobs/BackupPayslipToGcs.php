<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Payslip;
use Illuminate\Support\Facades\Storage;

class BackupPayslipToGcs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payslipId;
    public $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct($payslipId, $tenantId = null)
    {
        $this->payslipId = $payslipId;
        $this->tenantId = $tenantId ?: tenant('id');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->tenantId) {
            tenancy()->initialize($this->tenantId);
        }

        $payslip = Payslip::withoutGlobalScopes()->with(['employee', 'company', 'uploadBatch'])->find($this->payslipId);
        if (!$payslip || !$payslip->file_path) {
            return;
        }

        $localDisk = Storage::disk('local');
        if (!$localDisk->exists($payslip->file_path)) {
            return;
        }

        $tenantName = tenant('company_name') ?? tenant('id') ?? 'UnknownTenant';
        $companyName = $payslip->company ? $payslip->company->name : 'UnknownCompany';
        $year = $payslip->period_year;
        $month = str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT);
        $type = $payslip->liquidation_type ?? 'Mensual';
        
        $cuil = '00000000000';
        if ($payslip->employee_id) {
            $profile = \App\Models\EmployeeProfile::withoutGlobalScopes()
                ->where('user_id', $payslip->employee_id)
                ->where('company_id', $payslip->company_id)
                ->first();
            if ($profile && $profile->cuil) {
                $cuil = $profile->cuil;
            }
        }

        $batchName = $payslip->uploadBatch ? 'Lote-' . $payslip->uploadBatch->id : 'Lote-0';

        $isSigned = str_starts_with($payslip->status, 'signed_');
        $suffix = $isSigned ? '_firmado.pdf' : '_original.pdf';
        
        $fileName = $cuil . $suffix;
        
        $gcsPath = sprintf(
            "%s/%s/%s/%s/%s/%s/%s/%s",
            $tenantName,
            $companyName,
            $year,
            $month,
            $type,
            $cuil,
            $batchName,
            $fileName
        );

        // Sanitize path slightly to avoid issues with slashes in names
        $gcsPath = str_replace(['\\', '//'], '/', $gcsPath);

        $stream = $localDisk->readStream($payslip->file_path);
        Storage::disk('gcs')->put($gcsPath, $stream);
    }
}
