<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payslip;
use App\Models\User;
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
        $employee = User::with('employeeProfile')->findOrFail($id);
        $payslips = Payslip::with(['uploadBatch', 'signature'])
            ->where('employee_id', $employee->id)
            ->where('is_rectified', false)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        // Inicializar TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configurar metadatos del documento
        $pdf->SetCreator('BonosWeb System');
        $pdf->SetAuthor('Departamento de RRHH');
        $pdf->SetTitle('Auditoría de Firmas - ' . $employee->name);
        
        // Remover cabeceras y pies por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Añadir página
        $pdf->AddPage();

        // ---- CONFIGURAR FIRMA ELECTRÓNICA DEL TENANT/SISTEMA ----
        $crtPath = tenant('cert_path') ? storage_path(tenant('cert_path')) : storage_path('app/certs/system_cert.crt');
        $keyPath = tenant('cert_key_path') ? storage_path(tenant('cert_key_path')) : storage_path('app/certs/system_cert.key');
        $companyName = tenant('company_name') ?? 'BonosWeb System';
        
        if (file_exists($crtPath) && file_exists($keyPath)) {
            $info = array(
                'Name' => $companyName,
                'Location' => 'Servidor BonosWeb',
                'Reason' => 'Garantizar inmutabilidad del reporte de auditoría',
                'ContactInfo' => 'rrhh@' . (tenant('domains')->first()->domain ?? 'empresa.com'),
            );
            $pdf->setSignature('file://'.$crtPath, 'file://'.$keyPath, '', '', 2, $info);
        }

        // ---- CONTENIDO DEL REPORTE ----
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, 'Reporte de Auditoría de Conformidad', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Empleado: ' . $employee->name, 0, 1, 'L');
        $pdf->Cell(0, 10, 'CUIL: ' . ($employee->employeeProfile->cuil ?? 'N/A'), 0, 1, 'L');
        $pdf->Cell(0, 10, 'Fecha de Emisión: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
        
        $pdf->Ln(10);
        
        // Crear Tabla
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
            $type = ucfirst($p->liquidation_type);
            $status = $p->signature ? 'Firmado' : 'Pendiente';
            $date = $p->signature ? $p->signature->signed_at->format('d/m/Y H:i') : '-';
            $ip = $p->signature ? $p->signature->ip_address : '-';
            
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

        // Output (I = Inline/Browser)
        $pdf->Output('Auditoria_'.$employee->employeeProfile->cuil.'.pdf', 'I');
    }
}
