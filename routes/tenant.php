<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Livewire\Dashboard;
use App\Livewire\Auth\Login;
use App\Models\Company;
use App\Services\CompanyContextService;

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
    Route::get('/branding/logo/{company?}', function ($company = null) {
        if ($company) {
            $companyModel = \App\Models\Company::find($company);
        } else {
            $service = app(\App\Services\CompanyContextService::class);
            $companyModel = \App\Models\Company::find($service->getCurrentCompanyId());
        }

        $path = $companyModel?->logo_path;
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
        Route::get('/billing', App\Livewire\Billing\TenantBilling::class)->name('billing.index')->middleware('tenant.admin');

        // ── Rutas protegidas por contexto de empresa válido ───────────────────
        Route::middleware('tenant.context')->group(function () {
            // Empleado
            Route::get('/mis-bonos', App\Livewire\Employees\MisBonos::class)->name('employee.my-payslips');

            // RRHH / Admin
            Route::get('/employees', App\Livewire\Employees\Manager::class)->name('employees.index');
            Route::get('/employees/{id}/history', App\Livewire\Employees\History::class)->name('employees.history');
            Route::get('/employees/{id}/export-history', [App\Http\Controllers\PayslipController::class, 'exportHistory'])->name('employees.export-history');
            Route::get('/employees/{id}/download-zip', [App\Http\Controllers\PayslipController::class, 'downloadAllZip'])->name('employees.download-zip');

            Route::get('/payslips/upload', App\Livewire\Payslips\Upload::class)->name('payslips.upload');
            Route::get('/payslips/list', App\Livewire\Payslips\PayslipList::class)->name('payslips.list');
            Route::get('/payslips/{id}/view', [App\Http\Controllers\PayslipController::class, 'view'])->name('payslips.view');

            Route::get('/reports/signatures', App\Livewire\Reports\SignaturesTracking::class)->name('reports.signatures');
            Route::get('/reports/disconformities', App\Livewire\Reports\DisconformityReport::class)->name('reports.disconformities');
            Route::get('/configuracion/firma', App\Livewire\Tenant\SignatureConfigurator::class)->name('signature.configurator');
        });

        Route::get('/users', App\Livewire\Tenant\UsersManager::class)->name('users.index');
        Route::get('/empresas', App\Livewire\Tenant\CompanyCreator::class)->name('companies.create');

        // ── Secciones exclusivas de Administrador ────────────────────────────
        Route::middleware('tenant.admin')->group(function () {
            Route::get('/configuracion/branding', App\Livewire\Tenant\BrandingSettings::class)->name('branding.settings');
            Route::get('/configuracion/motivos-disconformidad', App\Livewire\Tenant\DisagreementReasons::class)->name('disagreement-reasons');
        });

        // Certificado Raíz (accesible para admin y rrhh)
        Route::get('/configuracion/certificado-raiz', App\Livewire\Tenant\RootCertificateDownload::class)
            ->name('root.certificate.download');

        // Servir imagen de previsualización (primera página del PDF modelo sin firma)
        Route::get('/configuracion/firma/preview-image', function () {
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = Company::find($companyId);
            $path = $company ? $company->signature_preview_path : null;
            if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) abort(404);
            return response()->file(
                \Illuminate\Support\Facades\Storage::disk('local')->path($path),
                ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-cache, no-store']
            );
        })->name('signature.preview');

        // Servir previsualización renderizada (PDF con la firma aplicada)
        Route::get('/configuracion/firma/preview-rendered', function () {
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = Company::find($companyId);
            
            if (!$company || empty($company->signature_preview_path) || empty($company->signature_image_path)) {
                abort(404);
            }
            
            $srcPath = \Illuminate\Support\Facades\Storage::disk('local')->path($company->signature_preview_path);
            $sigImagePath = \Illuminate\Support\Facades\Storage::disk('local')->path($company->signature_image_path);
            
            if (!file_exists($srcPath) || !file_exists($sigImagePath)) {
                abort(404);
            }

            try {
                $fpdi = new \App\Pdf\CustomFpdi();
                $fpdi->setSourceFile($srcPath);
                $tplId = $fpdi->importPage(1);
                $size = $fpdi->getTemplateSize($tplId);

                $fpdi->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

                $sigX = $company->signature_x ?? 0.0;
                $sigY = $company->signature_y ?? 0.0;
                $sigW = $company->signature_w ?? 40.0;
                $sigH = $company->signature_h ?? 20.0;
                
                if (!empty($company->signature_anchor_text)) {
                    $anchored = app(\App\Services\PdfCoordinateExtractor::class)->findCoordinates($srcPath, $company->signature_anchor_text);
                    if ($anchored) {
                        $offsetY = $company->signature_anchor_offset_y ?? 10.0;
                        $sigY = abs($size['height'] - $anchored['y_mm_from_bottom'] - $sigH - $offsetY);
                        if ($anchored['x_mm'] > 5.0) {
                            $sigX = $anchored['x_mm'] + 15 - ($sigW / 2);
                        }
                    }
                }

                $ext = strtolower(pathinfo($sigImagePath, PATHINFO_EXTENSION));
                if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $ext = 'png';
                }

                $fpdi->Image($sigImagePath, $sigX, $sigY, $sigW, $sigH, strtoupper($ext));
                $pdfContent = $fpdi->Output('', 'S');
                
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Cache-Control', 'no-cache, no-store');

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error generating rendered preview: " . $e->getMessage());
                return response()->file($srcPath, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-cache, no-store']);
            }
        })->name('signature.preview.rendered');

        // Servir imagen de la firma del empleador
        Route::get('/configuracion/firma/signature-image', function () {
            $companyId = app(CompanyContextService::class)->getCurrentCompanyId();
            $company = Company::find($companyId);
            $path = $company ? $company->signature_image_path : null;
            if (!$path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) abort(404);
            $absPath  = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
            $mimeType = mime_content_type($absPath) ?: 'image/png';
            return response()->file($absPath, ['Content-Type' => $mimeType, 'Cache-Control' => 'no-cache, no-store']);
        })->name('signature.image');
    });
});
