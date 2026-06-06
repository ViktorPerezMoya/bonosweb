<?php

namespace App\Livewire\Layouts;

use App\Models\Company;
use App\Services\CompanyContextService;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public ?string $companyName = null;
    public ?string $companyLogoUrl = null;
    public bool $hideNameInMenu = false;

    public function mount(CompanyContextService $contextService): void
    {
        $this->loadCompanyData($contextService);
    }

    #[On('company-changed')]
    public function loadCompanyData(CompanyContextService $contextService): void
    {
        if (function_exists('tenant') && tenant()) {
            $companyId = $contextService->getCurrentCompanyId();
            $company = Company::find($companyId);
            
            if ($company) {
                $this->companyName = $company->name;
                $this->companyLogoUrl = $company->logo_path ? route('branding.logo', $company->id) : null;
                $this->hideNameInMenu = $company->hide_name_in_menu ?? false;
            } else {
                $this->companyName = tenant('company_name');
                $this->companyLogoUrl = null;
                $this->hideNameInMenu = false;
            }
        }
    }

    public function render()
    {
        return view('livewire.layouts.sidebar');
    }
}
