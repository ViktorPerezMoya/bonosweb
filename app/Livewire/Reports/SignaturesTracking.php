<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UploadBatch;
use App\Models\Payslip;

class SignaturesTracking extends Component
{
    use WithPagination;

    public $selectedBatchId = null;

    public function updatedSelectedBatchId()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Obtener la lista de lotes completados para el selector
        $batches = UploadBatch::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        // Si no hay lote seleccionado y hay lotes, seleccionar el último
        if (!$this->selectedBatchId && $batches->count() > 0) {
            $this->selectedBatchId = $batches->first()->id;
        }

        // Obtener los recibos del lote seleccionado
        $payslips = collect();
        if ($this->selectedBatchId) {
            $payslips = Payslip::with(['employee', 'signature'])
                ->where('upload_batch_id', $this->selectedBatchId)
                ->where('is_rectified', false) // Solo mostramos los vigentes
                ->paginate(15);
        } else {
            $payslips = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return view('livewire.reports.signatures-tracking', [
            'batches' => $batches,
            'payslips' => $payslips
        ])->layout('components.layouts.app', [
            'header' => 'Estado de Firmas',
            'title' => 'Auditoría de Firmas - BonosWeb'
        ]);
    }
}
