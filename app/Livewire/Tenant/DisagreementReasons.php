<?php

namespace App\Livewire\Tenant;

use App\Models\DisagreementReason;
use App\Services\CompanyContextService;
use Livewire\Component;

class DisagreementReasons extends Component
{
    public $reasons = [];
    public $newReason = '';
    
    // For editing
    public $editingId = null;
    public $editingReasonText = '';

    protected $rules = [
        'newReason' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->loadReasons();
    }

    public function loadReasons()
    {
        $this->reasons = DisagreementReason::orderBy('id')->get();
    }

    public function addReason()
    {
        $this->validate();

        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
        
        if (!$companyId) {
            session()->flash('error', 'Debes tener una empresa seleccionada.');
            return;
        }

        DisagreementReason::create([
            'company_id' => $companyId,
            'reason_text' => $this->newReason,
            'is_active' => true,
        ]);

        $this->newReason = '';
        $this->loadReasons();
        session()->flash('success', 'Motivo agregado correctamente.');
    }

    public function toggleActive($id)
    {
        $reason = DisagreementReason::findOrFail($id);
        $reason->update([
            'is_active' => !$reason->is_active,
        ]);
        $this->loadReasons();
    }

    public function editReason($id)
    {
        $reason = DisagreementReason::findOrFail($id);
        $this->editingId = $id;
        $this->editingReasonText = $reason->reason_text;
    }

    public function updateReason()
    {
        $this->validate([
            'editingReasonText' => 'required|string|max:255',
        ]);

        $reason = DisagreementReason::findOrFail($this->editingId);
        $reason->update([
            'reason_text' => $this->editingReasonText,
        ]);

        $this->editingId = null;
        $this->editingReasonText = '';
        $this->loadReasons();
        session()->flash('success', 'Motivo actualizado correctamente.');
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editingReasonText = '';
    }

    public function render()
    {
        return view('livewire.tenant.disagreement-reasons')
            ->layout('components.layouts.app');
    }
}
