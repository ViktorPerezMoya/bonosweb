<?php

namespace App\Livewire\Auth;

use App\Models\EmployeeProfile;
use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Login con autenticación dinámica.
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │ Tipo de credencial detectada automáticamente:                            │
 * │  · Email válido  →  busca en users.email           →  Admin / RRHH      │
 * │  · 10-11 dígitos →  busca en employee_profiles.cuil →  Empleado         │
 * │  · 7-8 dígitos   →  busca en employee_profiles.document_number → Empl.  │
 * │                                                                          │
 * │ Redirección post-login:                                                  │
 * │  · Admin / RRHH  →  /dashboard  (panel de gestión)                      │
 * │  · Empleado      →  /mis-bonos  (vista móvil de recibos propios)         │
 * └──────────────────────────────────────────────────────────────────────────┘
 */
class Login extends Component
{
    /** Acepta: email, CUIL (10-11 dígitos) o DNI (7-8 dígitos). */
    public string $credential = '';
    public string $password   = '';
    public bool   $remember   = false;

    protected $rules = [
        'credential' => 'required|string',
        'password'   => 'required|string',
    ];

    protected $messages = [
        'credential.required' => 'Ingresá tu email, DNI o CUIL.',
        'password.required'   => 'La contraseña es requerida.',
    ];

    // ── Punto de entrada ──────────────────────────────────────────────────────

    public function login(): mixed
    {
        $this->validate();

        $input = trim($this->credential);

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return $this->loginWithEmail($input);
        }

        if (preg_match('/^\d{10,11}$/', $input)) {
            // CUIL argentino: 11 dígitos (o 10 en casos raros)
            return $this->loginWithDniOrCuil($input, 'cuil');
        }

        if (preg_match('/^\d{7,8}$/', $input)) {
            // DNI: 7 u 8 dígitos
            return $this->loginWithDniOrCuil($input, 'document_number');
        }

        $this->addError('credential', 'Ingresá un email válido, un DNI (7-8 dígitos) o un CUIL (11 dígitos).');
        return null;
    }

    // ── Estrategia 1: Login por Email (Admin / RRHH) ──────────────────────────

    private function loginWithEmail(string $email): mixed
    {
        if (! Auth::attempt(['email' => $email, 'password' => $this->password], $this->remember)) {
            $this->addError('credential', 'Las credenciales no coinciden con nuestros registros.');
            return null;
        }

        session()->regenerate();
        $role = Auth::user()->role;

        if (in_array($role, ['hr', 'admin'])) {
            $context = app(\App\Services\CompanyContextService::class);
            $context->getCurrentCompanyId(); // Inicializa sesión
            return redirect()->intended(route('dashboard'));
        }

        if ($role === 'employee') {
            // Un empleado que tiene email también puede iniciar sesión por email
            $context = app(\App\Services\CompanyContextService::class);
            $context->getCurrentCompanyId(); // Inicializa sesión
            return redirect()->route('employee.my-payslips');
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->addError('credential', 'Acceso denegado. Tu usuario no tiene un rol válido.');
        return null;
    }

    // ── Estrategia 2: Login por CUIL / DNI (Empleados) ───────────────────────

    private function loginWithDniOrCuil(string $value, string $field): mixed
    {
        $profile = EmployeeProfile::withoutGlobalScope(CurrentCompanyScope::class)
            ->where($field, $value)
            ->where('is_active', true)
            ->with('user')
            ->first();

        if (! $profile || ! $profile->user) {
            $this->addError('credential', 'No se encontró un empleado activo con ese dato.');
            return null;
        }

        if (! Hash::check($this->password, $profile->user->password)) {
            $this->addError('credential', 'Contraseña incorrecta.');
            return null;
        }

        Auth::login($profile->user, $this->remember);
        session()->regenerate();
        
        // Forzar inicialización de empresa en sesión
        session(['current_company_id' => $profile->company_id]);

        return redirect()->route('employee.my-payslips');
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\Contracts\View\View
    {
        $bgUrl = null;
        $mainCompanyLogoUrl = null;
        if (function_exists('tenant') && $t = tenant()) {
            $bgUrl = $t->loginBackgroundUrl();
            
            $mainCompany = \App\Models\Company::where('is_main', true)->first();
            if ($mainCompany && $mainCompany->logo_path) {
                $mainCompanyLogoUrl = route('branding.logo', $mainCompany->id);
            }
        }
        return view('livewire.auth.login', [
            'bgUrl' => $bgUrl,
            'mainCompanyLogoUrl' => $mainCompanyLogoUrl,
        ])->layout('components.layouts.app');
    }
}
