<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TCPDF;

class PayslipController extends Controller
{
    public function view(Request $request, $id)
    {
        $payslip = Payslip::findOrFail($id);
        $path = Storage::disk('local')->path($payslip->file_path);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        if ($request->has('download')) {
            return response()->download($path, $payslip->original_filename);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$payslip->original_filename.'"'
        ]);
    }

    public function exportHistory($id)
    {
        $employee = User::with('currentCompanyProfile')->findOrFail($id);
        $payslips = Payslip::with(['uploadBatch', 'signature'])
            ->where('employee_id', $employee->id)
            ->where('is_rectified', false)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        $companyId = app(\App\Services\CompanyContextService::class)->getCurrentCompanyId();
        $companyName = \App\Models\Company::find($companyId)->name ?? 'BonosWeb System';

        // ── 1. Inicializar TCPDF ──────────────────────────────────────────
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('BonosWeb System');
        $pdf->SetAuthor($companyName);
        $pdf->SetTitle('Auditoría de Firmas - ' . $employee->name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // ── 2. Firma digital (ANTES de AddPage) ──────────────────────────
        // Los certificados se guardan en el disco central (storage/app/private/),
        // independiente del override de Stancl por tenant. Por eso se construye
        // el path absoluto con storage_path('app/private/') en lugar de storage_path().
        $certRelPath = tenant('cert_path');
        $keyRelPath  = tenant('cert_key_path');

        Log::info('[exportHistory] cert_path=' . var_export($certRelPath, true)
            . ' | cert_key_path=' . var_export($keyRelPath, true));

        $signatureApplied = false;

        if ($certRelPath && $keyRelPath) {
            // storage_path() en contexto tenant es sobreescrito por Stancl a storage/tenant{id}/
            // Los certs se guardaron en el disco CENTRAL (storage/app/private/) usando Storage::disk('local')
            // base_path() NO es sobreescrito por Stancl → única forma de referenciar el storage central
            $centralBase = base_path('storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR);
            $crtAbsPath = $centralBase . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $certRelPath), DIRECTORY_SEPARATOR);
            $keyAbsPath = $centralBase . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $keyRelPath), DIRECTORY_SEPARATOR);

            Log::info('[exportHistory] crtAbsPath=' . $crtAbsPath . ' exists=' . (file_exists($crtAbsPath) ? 'SI' : 'NO'));
            Log::info('[exportHistory] keyAbsPath=' . $keyAbsPath . ' exists=' . (file_exists($keyAbsPath) ? 'SI' : 'NO'));

            if (file_exists($crtAbsPath) && file_exists($keyAbsPath)) {
                $domain   = optional(tenant('domains')->first())->domain ?? 'bonosweb.com';
                $signInfo = [
                    'Name'        => $companyName,
                    'Location'    => 'Servidor BonosWeb',
                    'Reason'      => 'Certificación del reporte de auditoría de conformidad',
                    'ContactInfo' => 'rrhh@' . $domain,
                ];
                // cert_type=3: documento certificado, modificaciones de formularios y firmas permitidas
                $pdf->setSignature(
                    'file://' . $crtAbsPath,
                    'file://' . $keyAbsPath,
                    '',        // contraseña de la clave privada (vacía, sin protección)
                    '',        // cadena de certificados intermedios (no aplica)
                    3,
                    $signInfo
                );
                $signatureApplied = true;
                Log::info('[exportHistory] setSignature() llamado correctamente');
            }
        }

        // ── 3. Contenido ─────────────────────────────────────────────────
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, 'Reporte de Auditoría de Conformidad', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Empleado: ' . $employee->name, 0, 1, 'L');
        $pdf->Cell(0, 10, 'CUIL: ' . ($employee->currentCompanyProfile->cuil ?? 'N/A'), 0, 1, 'L');
        $pdf->Cell(0, 10, 'Fecha de Emisión: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

        $pdf->Ln(10);

        $html = '<table border="1" cellpadding="5">
                    <thead>
                        <tr style="background-color: #f0f0f0; font-weight: bold;">
                            <th width="20%">Período</th>
                            <th width="20%">Tipo</th>
                            <th width="20%">Estado</th>
                            <th width="20%">Fecha Firma</th>
                            <th width="20%">IP Dispositivo</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($payslips as $p) {
            $period = $p->period_year . '-' . str_pad($p->period_month, 2, '0', STR_PAD_LEFT);
            $type   = ucfirst($p->liquidation_type);
            $status = 'Pendiente';
            if ($p->status === 'signed_conforme') $status = 'Conforme';
            elseif ($p->status === 'signed_no_conforme') $status = 'No Conforme';
            $date   = $p->signature ? $p->signature->signed_at->format('d/m/Y H:i') : '-';
            $ip     = $p->signature ? htmlspecialchars($p->signature->ip_address) : '-';

            $html .= "<tr>
                        <td>{$period}</td>
                        <td>{$type}</td>
                        <td>{$status}</td>
                        <td>{$date}</td>
                        <td>{$ip}</td>
                      </tr>";
        }

        $html .= '</tbody></table>';

        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->MultiCell(0, 10, "Este documento ha sido generado automáticamente por el sistema BonosWeb para la empresa {$companyName}. Posee un sello criptográfico (Firma Electrónica) emitido a nombre de {$companyName} que garantiza que su contenido no ha sido alterado tras su emisión.", 0, 'C');

        // ── 4. Output ─────────────────────────────────────────────────────
        $pdf->Output('Auditoria_' . ($employee->currentCompanyProfile->cuil ?? $id) . '.pdf', 'I');
    }

    public function signCryptographically($payslip, $employee, $signatureType, $disagreementReasonId = null)
    {
        $profile = \App\Models\EmployeeProfile::withoutGlobalScope(\App\Models\Scopes\CurrentCompanyScope::class)
            ->where('user_id', $employee->id)
            ->where('company_id', $payslip->company_id)
            ->first();

        if (!$profile || !$profile->certificate_path) {
            throw new \Exception('No se encontró el certificado digital del empleado.');
        }

        $certAbsPath = Storage::disk('local')->path($profile->certificate_path);
        
        if (!file_exists($certAbsPath)) {
            throw new \Exception('El archivo del certificado no existe en el servidor.');
        }

        if ($signatureType === 'conforme') {
            $reasonText = 'FIRMADO CONFORME';
        } else {
            $reasonModel = \App\Models\DisagreementReason::find($disagreementReasonId);
            $reasonText = 'NO CONFORME: ' . ($reasonModel ? $reasonModel->reason_text : 'Motivo no especificado');
        }

        $originalPath = Storage::disk('local')->path($payslip->file_path);
        
        $signInfo = [
            'Name'        => $employee->name,
            'Location'    => 'Portal del Empleado (BonosWeb)',
            'Reason'      => $reasonText,
        ];

        $plainPassword = \Illuminate\Support\Facades\Crypt::decrypt($profile->certificate_password);
        
        // Crear un archivo temporal para el output
        $tempOutput = tempnam(sys_get_temp_dir(), 'signed_pdf_');
        
        // Ruta al script de python
        $scriptPath = base_path('storage/scripts/sign_pdf.py');
        
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $pythonBin = $isWindows ? 'python' : 'python3';
        
        $env = $_SERVER;
        $env['PATH'] = getenv('PATH');
        if ($isWindows && empty($env['SystemRoot'])) {
            $env['SystemRoot'] = getenv('SystemRoot') ?: 'C:\\Windows';
        }

        // Ejecutar el script
        $process = new \Symfony\Component\Process\Process(
            [
                $pythonBin,
                $scriptPath,
                '--in', $originalPath,
                '--out', $tempOutput,
                '--pfx', $certAbsPath,
                '--pwd', $plainPassword,
                '--reason', $signInfo['Reason'],
                '--location', $signInfo['Location'],
                '--name', $signInfo['Name']
            ],
            null,
            $env
        );
        
        $process->run();
        
        if (!$process->isSuccessful()) {
            @unlink($tempOutput);
            throw new \Exception('Error ejecutando pyHanko: ' . $process->getErrorOutput() . ' | STDOUT: ' . $process->getOutput());
        }
        
        $output = $process->getOutput();
        if (strpos($output, 'SUCCESS') === false) {
            @unlink($tempOutput);
            throw new \Exception('Error en pyHanko al firmar: ' . $output);
        }
        
        // Reemplazar el archivo original con el firmado incrementalmente
        if (!rename($tempOutput, $originalPath)) {
            // Fallback: copy and unlink if cross-device rename fails (e.g. from C:\tmp to C:\laragon)
            copy($tempOutput, $originalPath);
            @unlink($tempOutput);
        }
    }

    public function downloadAllZip($id)
    {
        $payslips = Payslip::where('employee_id', $id)->where('is_rectified', false)->get();

        if ($payslips->isEmpty()) {
            return back()->with('error', 'El empleado no tiene recibos válidos para descargar.');
        }

        $employee = User::with('currentCompanyProfile')->findOrFail($id);
        $cuil = $employee->currentCompanyProfile->cuil ?? $id;

        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive();

        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($payslips as $payslip) {
                $absolutePath = Storage::disk('local')->path($payslip->file_path);
                
                if (file_exists($absolutePath)) {
                    $year = str_pad($payslip->period_year, 4, '0', STR_PAD_LEFT);
                    $month = str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT);
                    $filenameInZip = "{$year}_{$month}_{$cuil}_{$payslip->original_filename}";
                    
                    $zip->addFile($absolutePath, $filenameInZip);
                }
            }
            $zip->close();
        }

        return response()->download($tempFile, "recibos_{$cuil}.zip")->deleteFileAfterSend(true);
    }
}
