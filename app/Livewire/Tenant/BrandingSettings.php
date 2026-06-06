<?php

namespace App\Livewire\Tenant;

use App\Models\Company;
use App\Services\CompanyContextService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class BrandingSettings extends Component
{
    use WithFileUploads;

    // ── Uploads temporales ────────────────────────────────────────────────────
    public $logo;
    public $loginBackground;

    // ── URLs y configuraciones actuales ───────────────────────────────────────
    public ?string $currentLogoUrl = null;
    public ?string $currentBgUrl   = null;
    public bool $hideNameInMenu    = false;

    // ── Reglas de validación ──────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'logo'            => 'nullable|file|mimes:png,svg,jpg,jpeg|max:2048',
            'loginBackground' => 'nullable|file|mimes:jpeg,jpg,webp|max:3072',
            'hideNameInMenu'  => 'boolean',
        ];
    }

    public function mount(CompanyContextService $contextService): void
    {
        abort_if(auth()->user()->role !== 'admin', 403);

        $t = tenant();
        $this->currentBgUrl = $t->loginBackgroundUrl();
        
        $company = Company::find($contextService->getCurrentCompanyId());
        $this->currentLogoUrl = ($company && $company->logo_path) ? route('branding.logo', $company->id) : null;
        $this->hideNameInMenu = $company->hide_name_in_menu ?? false;
    }

    #[On('company-changed')]
    public function reloadLogo(): void
    {
        $contextService = app(CompanyContextService::class);
        $company = Company::find($contextService->getCurrentCompanyId());
        $this->currentLogoUrl = ($company && $company->logo_path) ? route('branding.logo', $company->id) : null;
        $this->hideNameInMenu = $company->hide_name_in_menu ?? false;
    }

    public function updatedHideNameInMenu()
    {
        $contextService = app(CompanyContextService::class);
        $company = Company::findOrFail($contextService->getCurrentCompanyId());
        $company->update(['hide_name_in_menu' => $this->hideNameInMenu]);
        session()->flash('logo_saved', 'Configuración del menú actualizada.');
    }

    // ── Guardar logo ──────────────────────────────────────────────────────────
    public function saveLogo(CompanyContextService $contextService): void
    {
        $this->validateOnly('logo', ['logo' => 'required|file|mimes:png,svg,jpg,jpeg|max:2048']);

        $company = Company::findOrFail($contextService->getCurrentCompanyId());

        if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $ext  = $this->logo->getClientOriginalExtension();
        // Generar nombre unico para que no hayan conflictos entre empresas
        $filename = "logo_company_{$company->id}_" . time() . ".{$ext}";
        $path = $this->logo->storeAs('branding', $filename, 'public');

        // Automatically set hide_name_in_menu if aspect ratio > 2
        try {
            $absPath = Storage::disk('public')->path($path);
            $size = getimagesize($absPath);
            if ($size && $size[1] > 0) {
                $aspectRatio = $size[0] / $size[1];
                if ($aspectRatio > 2.0) {
                    $this->hideNameInMenu = true;
                }
            }
        } catch (\Throwable $th) {
            // Ignorar errores de getimagesize
        }

        $company->update([
            'logo_path' => $path,
            'hide_name_in_menu' => $this->hideNameInMenu,
        ]);

        $this->currentLogoUrl = route('branding.logo', $company->id);
        $this->logo           = null;

        session()->flash('logo_saved', 'Logo actualizado correctamente.');
    }

    // ── Guardar fondo de login ────────────────────────────────────────────────
    public function saveBackground(): void
    {
        $this->validateOnly('loginBackground', ['loginBackground' => 'required|file|mimes:jpeg,jpg,webp|max:3072']);

        $t = tenant();

        if ($t->login_background_path && Storage::disk('public')->exists($t->login_background_path)) {
            Storage::disk('public')->delete($t->login_background_path);
        }

        $ext  = $this->loginBackground->getClientOriginalExtension();
        // Generar nombre unico para evitar cacheo no deseado
        $filename = "background_" . time() . ".{$ext}";
        $path = $this->loginBackground->storeAs('branding', $filename, 'public');

        $t->update(['login_background_path' => $path]);

        $this->currentBgUrl      = route('branding.background');
        $this->loginBackground   = null;

        session()->flash('bg_saved', 'Fondo de login actualizado correctamente.');
    }

    // ── Eliminar logo ─────────────────────────────────────────────────────────
    public function removeLogo(CompanyContextService $contextService): void
    {
        $company = Company::findOrFail($contextService->getCurrentCompanyId());

        if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->update(['logo_path' => null]);
        $this->currentLogoUrl = null;

        session()->flash('logo_saved', 'Logo eliminado.');
    }

    // ── Eliminar fondo ────────────────────────────────────────────────────────
    public function removeBackground(): void
    {
        $t = tenant();

        if ($t->login_background_path && Storage::disk('public')->exists($t->login_background_path)) {
            Storage::disk('public')->delete($t->login_background_path);
        }

        $t->update(['login_background_path' => null]);
        $this->currentBgUrl = null;

        session()->flash('bg_saved', 'Fondo de login eliminado.');
    }

    public function render()
    {
        return view('livewire.tenant.branding-settings')
            ->layout('components.layouts.app', [
                'title' => 'Identidad Visual - BonosWeb',
            ]);
    }
}
