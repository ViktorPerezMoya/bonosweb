<?php

namespace App\Livewire\Employees;

use App\Models\Payslip;
use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vista de empleado: muestra únicamente los recibos de sueldo propios.
 *
 * Accesible con rol 'employee' (redirigido desde Login).
 * No muestra recibos de otros empleados; no necesita CompanyContextService
 * porque el empleado ve sus recibos de TODAS las empresas del tenant.
 */
class MisBonos extends Component
{
    use WithPagination;

    public $selectedPayslipId = null;
    public $showModal = false;
    public $signatureType = 'conforme'; // 'conforme' o 'no_conforme'
    public $signaturePassword = '';
    public $disagreementReasonId = null;

    protected $rules = [
        'signaturePassword' => 'required',
    ];

    public function openViewer($id)
    {
        $this->selectedPayslipId = $id;
        $this->showModal = true;
        $this->signaturePassword = '';
        $this->signatureType = 'conforme';
        $this->disagreementReasonId = null;
    }

    public function closeViewer()
    {
        $this->showModal = false;
        $this->selectedPayslipId = null;
        $this->signaturePassword = '';
    }

    public function setSignatureType($type)
    {
        $this->signatureType = $type;
        $this->signaturePassword = '';
        $this->disagreementReasonId = null;
    }

    #[Computed]
    public function selectedPayslip()
    {
        return $this->selectedPayslipId ? Payslip::find($this->selectedPayslipId) : null;
    }

    #[Computed]
    public function activeReasons()
    {
        $payslip = $this->selectedPayslip;
        if (!$payslip) return collect();
        
        return \App\Models\DisagreementReason::where('company_id', $payslip->company_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function signPayslip()
    {
        \Illuminate\Support\Facades\Log::info("Iniciando firma para recibo ID: " . $this->selectedPayslipId);

        if (!$this->selectedPayslipId) {
            \Illuminate\Support\Facades\Log::warning("Fallo al iniciar firma: selectedPayslipId es nulo.");
            session()->flash('error', 'El recibo no está disponible para firmar.');
            return;
        }

        $this->validate([
            'signaturePassword' => 'required|string',
            'disagreementReasonId' => [
                \Illuminate\Validation\Rule::requiredIf($this->signatureType === 'no_conforme'),
                'nullable',
                'exists:disagreement_reasons,id'
            ]
        ], [
            'signaturePassword.required' => 'La contraseña es obligatoria.',
            'disagreementReasonId.required' => 'Debes seleccionar un motivo.',
            'disagreementReasonId.exists' => 'El motivo seleccionado no es válido.'
        ]);
        
        \Illuminate\Support\Facades\Log::info("Validación de formulario superada.");

        $payslip = $this->selectedPayslip;
        if (!$payslip || $payslip->status !== 'pending') {
            \Illuminate\Support\Facades\Log::warning("El recibo no existe o no está pendiente.");
            session()->flash('error', 'El recibo no está disponible para firmar.');
            return;
        }

        if (!\Illuminate\Support\Facades\Hash::check($this->signaturePassword, \Illuminate\Support\Facades\Auth::user()->password)) {
            \Illuminate\Support\Facades\Log::warning("Contraseña incorrecta al intentar firmar recibo ID: " . $this->selectedPayslipId);
            $this->addError('signaturePassword', 'La contraseña ingresada es incorrecta.');
            return;
        }
        
        \Illuminate\Support\Facades\Log::info("Contraseña validada correctamente.");

        // Verificamos el perfil y certificado antes del procesamiento
        $profile = \App\Models\EmployeeProfile::withoutGlobalScope(\App\Models\Scopes\CurrentCompanyScope::class)
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('company_id', $payslip->company_id)
            ->first();

        if (!$profile || !$profile->certificate_path) {
            \Illuminate\Support\Facades\Log::error("Perfil sin certificado digital para el usuario ID: " . \Illuminate\Support\Facades\Auth::id());
            $this->addError('signaturePassword', 'No tienes un certificado digital generado.');
            return;
        }

        \Illuminate\Support\Facades\Log::info("Iniciando procesamiento con TCPDF/OpenSSL.");

        try {
            // Lógica criptográfica (TCPDF)
            app(\App\Http\Controllers\PayslipController::class)->signCryptographically(
                $payslip, 
                \Illuminate\Support\Facades\Auth::user(), 
                $this->signatureType, 
                $this->disagreementReasonId
            );

            // Determinar texto del motivo si hay uno
            $disconformityReasonText = null;
            if ($this->signatureType !== 'conforme' && $this->disagreementReasonId) {
                $reasonModel = \App\Models\DisagreementReason::find($this->disagreementReasonId);
                if ($reasonModel) {
                    $disconformityReasonText = $reasonModel->reason_text;
                }
            }

            // Actualizar estado
            $payslip->update([
                'status' => $this->signatureType === 'conforme' ? 'signed_conforme' : 'signed_no_conforme',
                'signed_at' => now(),
                'disagreement_reason_id' => $this->signatureType === 'conforme' ? null : $this->disagreementReasonId,
                'disconformity_reason' => $disconformityReasonText,
            ]);

            // Crear registro de auditoría de firma
            $payslip->signature()->create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'pdf_hash' => hash_file('sha256', \Illuminate\Support\Facades\Storage::disk('local')->path($payslip->file_path)),
                'ip_address' => request()->ip(),
                'device_info' => request()->userAgent(),
                'signed_at' => now(),
            ]);

            \App\Jobs\BackupPayslipToGcs::dispatch($payslip->id, tenant('id'));

            \Illuminate\Support\Facades\Log::info("Recibo firmado exitosamente en BD. ID: " . $this->selectedPayslipId);
            session()->flash('success', 'Recibo firmado correctamente.');
            $this->dispatch('signature-success');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error al firmar recibo: " . $e->getMessage() . " - Linea: " . $e->getLine());
            session()->flash('error', 'Ocurrió un error al firmar el documento. Contacte a soporte.');
        }
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\Contracts\View\View
    {
        $payslips = Payslip::where('employee_id', Auth::id())
            ->where('is_rectified', false)       // Ocultar los que fueron reemplazados
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(15);

        return view('livewire.employees.mis-bonos', compact('payslips'))
            ->layout('components.layouts.app');
    }
}
