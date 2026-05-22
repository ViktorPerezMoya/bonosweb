<?php

namespace App\Livewire\Employees;

use Livewire\Component;
use App\Models\User;
use App\Models\Payslip;

class History extends Component
{
    public $employeeId;
    public $employee;
    public $selectedPdfUrl = null;
    public $showPdfModal = false;

    public function mount($id)
    {
        $this->employeeId = $id;
        $this->employee = User::with('employeeProfile')->findOrFail($id);
    }

    public function viewPdf($payslipId)
    {
        // Generar URL para ver el PDF de forma segura
        $this->selectedPdfUrl = route('payslips.view', ['id' => $payslipId]);
        $this->showPdfModal = true;
    }

    public function closePdfModal()
    {
        $this->showPdfModal = false;
        $this->selectedPdfUrl = null;
    }

    public function render()
    {
        $payslips = Payslip::with(['uploadBatch', 'signature'])
            ->where('employee_id', $this->employeeId)
            ->where('is_rectified', false) // Mostramos solo los vigentes
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        return view('livewire.employees.history', [
            'payslips' => $payslips
        ])->layout('components.layouts.app', [
            'header' => 'Historial de: ' . $this->employee->name,
            'title' => 'Historial Empleado - BonosWeb'
        ]);
    }
}
