<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Livewire\Dashboard;
use App\Livewire\Auth\Login;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', Login::class)->name('login')->middleware('guest');

    // ── Branding assets (sin auth — necesario para fondo en login) ────────────
    Route::get('/branding/logo', function () {
        $path = tenant('logo_path');
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) abort(404);
        $abs      = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        $mimeType = mime_content_type($abs) ?: 'image/png';
        return response()->file($abs, ['Content-Type' => $mimeType, 'Cache-Control' => 'public, max-age=3600']);
    })->name('branding.logo');

    Route::get('/branding/background', function () {
        $path = tenant('login_background_path');
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) abort(404);
        $abs      = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        $mimeType = mime_content_type($abs) ?: 'image/jpeg';
        return response()->file($abs, ['Content-Type' => $mimeType, 'Cache-Control' => 'public, max-age=3600']);
    })->name('branding.background');

    Route::middleware('guest')->group(function() {
        Route::get('/forgot-password', App\Livewire\Auth\ForgotPassword::class)->name('password.request');
        Route::get('/reset-password/{token}', App\Livewire\Auth\ResetPassword::class)->name('password.reset');
    });

    Route::post('/logout', function () {
        Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/profile', App\Livewire\Profile\UpdateProfile::class)->name('profile.show');
        Route::get('/billing', App\Livewire\Billing\TenantBilling::class)->name('billing.index');

        Route::get('/employees', App\Livewire\Employees\Manager::class)->name('employees.index');
        Route::get('/employees/{id}/history', App\Livewire\Employees\History::class)->name('employees.history');
        Route::get('/employees/{id}/export-history', [App\Http\Controllers\PayslipController::class, 'exportHistory'])->name('employees.export-history');

        Route::get('/payslips/upload', App\Livewire\Payslips\Upload::class)->name('payslips.upload');
        Route::get('/payslips/list', App\Livewire\Payslips\PayslipList::class)->name('payslips.list');
        Route::get('/payslips/{id}/view', [App\Http\Controllers\PayslipController::class, 'view'])->name('payslips.view');

        Route::get('/reports/signatures', App\Livewire\Reports\SignaturesTracking::class)->name('reports.signatures');

        Route::get('/users', App\Livewire\Tenant\UsersManager::class)->name('users.index');

        // ── Configuración de firma visual del empleador ───────────────────────
        Route::get('/configuracion/firma', App\Livewire\Tenant\SignatureConfigurator::class)->name('signature.configurator');

        // ── Identidad visual del tenant (logo + fondo de login) ───────────────
        Route::get('/configuracion/branding', App\Livewire\Tenant\BrandingSettings::class)->name('branding.settings');

        // Servir imagen de previsualización (primera página del PDF modelo)
        Route::get('/configuracion/firma/preview-image', function () {
            $path = tenant('signature_preview_path');
            if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) abort(404);
            return response()->file(
                \Illuminate\Support\Facades\Storage::disk('local')->path($path),
                ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-cache, no-store']
            );
        })->name('signature.preview');

        // Servir imagen de la firma del empleador
        Route::get('/configuracion/firma/signature-image', function () {
            $path = tenant('signature_image_path');
            if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) abort(404);
            $absPath  = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
            $mimeType = mime_content_type($absPath) ?: 'image/png';
            return response()->file($absPath, ['Content-Type' => $mimeType, 'Cache-Control' => 'no-cache, no-store']);
        })->name('signature.image');
    });
});
