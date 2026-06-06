<?php

namespace App\Livewire\Tenant;

use App\Models\Company;
use App\Services\CompanyContextService;
use Livewire\Component;

class CompanySwitcher extends Component
{
    /** @var \Illuminate\Support\Collection */
    public $companies;

    public ?int $currentCompanyId = null;
    public string $currentCompanyName = '';

    public function mount(): void
    {
        $this->loadState();
    }

    private function loadState(): void
    {
        $user = auth()->user();

        // Los empleados solo ven las empresas donde tienen un legajo activo.
        // Admin y RRHH ven todas las empresas del tenant.
        if ($user->role === 'employee') {
            $this->companies = $user->companies()
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get();
        } else {
            $this->companies = Company::orderByDesc('is_main')->orderBy('name')->get();
        }

        $this->currentCompanyId   = app(CompanyContextService::class)->getCurrentCompanyId();
        $current                  = $this->companies->firstWhere('id', $this->currentCompanyId);
        $this->currentCompanyName = $current?->name ?? '—';
    }

    public function switch(int $companyId): void
    {
        app(CompanyContextService::class)->setCurrentCompanyId($companyId);

        // Recarga la página actual con el nuevo contexto
        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.tenant.company-switcher');
    }
}
