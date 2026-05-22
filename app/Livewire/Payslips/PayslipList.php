<?php

namespace App\Livewire\Payslips;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UploadBatch;

class PayslipList extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $batches = UploadBatch::with('uploader')
            ->when($this->search, function ($query) {
                $query->where('original_filename', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.payslips.payslip-list', [
            'batches' => $batches
        ])->layout('components.layouts.app', [
            'header' => 'Lotes de Recibos Procesados',
            'title' => 'Lotes Procesados - BonosWeb'
        ]);
    }
}
