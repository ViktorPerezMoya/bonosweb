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
        // Los admins ven todas las empresas activas del tenant.
        // Los RRHH ven solo las empresas activas que tienen explícitamente asignadas.
        if ($user->role === 'employee') {
            $this->companies = $user->companies()
                ->where('employee_profiles.is_active', true)
                ->where('companies.is_active', true)
                ->orderByDesc('companies.is_main')
                ->orderBy('companies.name')
                ->get();
        } elseif ($user->role === 'hr') {
            $this->companies = $user->accessibleCompanies()
                ->where('companies.is_active', true)
                ->orderByDesc('companies.is_main')
                ->orderBy('companies.name')
                ->get();
        } else {
            $this->companies = Company::where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get();
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
