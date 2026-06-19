<?php

namespace App\Livewire\Reports;

use App\Models\Payslip;
use App\Models\DisagreementReason;
use Livewire\Component;
use Livewire\WithPagination;

class DisconformityReport extends Component
{
    use WithPagination;

    public $searchEmployee = '';
    public $searchReason   = '';
    public $searchYear     = '';
    public $searchMonth    = '';

    public $sortField     = 'created_at';
    public $sortDirection = 'desc';

    public function updatingSearchEmployee()
    {
        $this->resetPage();
    }

    public function updatingSearchReason()
    {
        $this->resetPage();
    }

    public function updatingSearchYear()
    {
        $this->resetPage();
    }

    public function updatingSearchMonth()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    protected function getFilteredQuery()
    {
        return Payslip::with(['employee', 'disagreementReason', 'uploadBatch'])
            ->where('status', 'signed_no_conforme')
            ->when($this->searchEmployee !== '', function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchEmployee . '%');
                });
            })
            ->when($this->searchReason !== '', function ($query) {
                $query->where('disagreement_reason_id', $this->searchReason);
            })
            ->when($this->searchYear !== '', function ($query) {
                $query->where('period_year', $this->searchYear);
            })
            ->when($this->searchMonth !== '', function ($query) {
                $query->where('period_month', $this->searchMonth);
            });
    }

    public function exportToExcel()
    {
        $query = $this->getFilteredQuery();
        
        // Custom sort resolution for related fields if needed, but simple fallback to query logic
        if (in_array($this->sortField, ['employee.name', 'disagreementReason.reason'])) {
            // For Excel export, we'll just get the collection and sort it, 
            // since sorting by relationship in raw SQL requires joins.
            $payslips = $query->get();
            if ($this->sortDirection === 'asc') {
                $payslips = $payslips->sortBy($this->sortField);
            } else {
                $payslips = $payslips->sortByDesc($this->sortField);
            }
        } else {
            $payslips = $query->orderBy($this->sortField, $this->sortDirection)->get();
        }

        $filename = 'auditoria_disconformidades_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($payslips) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header row
            fputcsv($file, [
                'ID',
                'EMPLEADO',
                'LIQUIDACION (Tipo)',
                'PERIODO',
                'MOTIVO DE NO CONFORMIDAD',
                'LOTE DE ORIGEN',
                'FECHA DE CREACION'
            ],";");

            foreach ($payslips as $payslip) {
                $employeeName = $payslip->employee->name ?? 'N/A';
                $reasonName = $payslip->disagreementReason->reason ?? 'Otro / No especificado';
                if ($payslip->disconformity_reason) {
                     $reasonName .= ' - ' . $payslip->disconformity_reason;
                }
                $period = $payslip->period_year . ' - Mes ' . str_pad($payslip->period_month, 2, '0', STR_PAD_LEFT);
                $batchType = $payslip->liquidation_type;
                $batchId = $payslip->uploadBatch->id ?? 'N/A';
                
                fputcsv($file, [
                    $payslip->id,
                    $employeeName,
                    ucfirst($batchType),
                    $period,
                    $reasonName,
                    $batchId,
                    $payslip->created_at->format('Y-m-d H:i:s')
                ],";");
            }
            fclose($file);
        }, $filename, $headers);
    }

    public function render()
    {
        $reasons = DisagreementReason::where('is_active', true)->get();
        
        $query = $this->getFilteredQuery();

        if ($this->sortField === 'employee.name') {
            // Paginate doesn't support sortBy collection well without manual handling
            // We'll use a join for database level sorting of relationship
            $query->join('users', 'payslips.employee_id', '=', 'users.id')
                  ->orderBy('users.name', $this->sortDirection)
                  ->select('payslips.*'); // Ensure we only get payslip columns to avoid conflicts
        } elseif ($this->sortField === 'disagreementReason.reason') {
            $query->leftJoin('disagreement_reasons', 'payslips.disagreement_reason_id', '=', 'disagreement_reasons.id')
                  ->orderBy('disagreement_reasons.reason', $this->sortDirection)
                  ->select('payslips.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $payslips = $query->paginate(15);

        return view('livewire.reports.disconformity-report', [
            'payslips' => $payslips,
            'reasons'  => $reasons
        ])->layout('components.layouts.app', [
            'header' => 'Auditoría de Disconformidades',
            'title' => 'Auditoría de Disconformidades - BonosWeb'
        ]);
    }
}
