<?php

namespace App\Livewire\Tenant;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BrandingSettings extends Component
{
    use WithFileUploads;

    // ── Uploads temporales ────────────────────────────────────────────────────
    public $logo;
    public $loginBackground;

    // ── URLs actuales (ya guardadas) ──────────────────────────────────────────
    public ?string $currentLogoUrl = null;
    public ?string $currentBgUrl   = null;

    // ── Reglas de validación ──────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'logo'            => 'nullable|file|mimes:png,svg|max:1024',
            'loginBackground' => 'nullable|file|mimes:jpeg,jpg,webp|max:3072',
        ];
    }

    public function mount(): void
    {
        $t = tenant();
        $this->currentLogoUrl = $t->logoUrl();
        $this->currentBgUrl   = $t->loginBackgroundUrl();
    }

    // ── Guardar logo ──────────────────────────────────────────────────────────
    public function saveLogo(): void
    {
        $this->validateOnly('logo', ['logo' => 'required|file|mimes:png,svg|max:1024']);

        $t = tenant();

        if ($t->logo_path && Storage::disk('public')->exists($t->logo_path)) {
            Storage::disk('public')->delete($t->logo_path);
        }

        $ext  = $this->logo->getClientOriginalExtension();
        $path = $this->logo->storeAs('branding', "logo.{$ext}", 'public');

        $t->update(['logo_path' => $path]);

        $this->currentLogoUrl = route('branding.logo');
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
        $path = $this->loginBackground->storeAs('branding', "background.{$ext}", 'local');

        $t->update(['login_background_path' => $path]);

        $this->currentBgUrl      = route('branding.background');
        $this->loginBackground   = null;

        session()->flash('bg_saved', 'Fondo de login actualizado correctamente.');
    }

    // ── Eliminar logo ─────────────────────────────────────────────────────────
    public function removeLogo(): void
    {
        $t = tenant();

        if ($t->logo_path && Storage::disk('public')->exists($t->logo_path)) {
            Storage::disk('public')->delete($t->logo_path);
        }

        $t->update(['logo_path' => null]);
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
