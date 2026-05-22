<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Central (SuperAdmin) Routes
|--------------------------------------------------------------------------
*/

$domain = env('APP_ENV') === 'local' ? null : 'admin.' . env('APP_URL_BASE', 'bonosweb.com');
$prefix = env('APP_ENV') === 'local' ? 'superadmin' : '';

Route::group(['domain' => $domain, 'prefix' => $prefix], function () {
    Route::get('/', function () {
        return redirect()->route('superadmin.login');
    });

    Route::get('/login', App\Livewire\SuperAdmin\Login::class)->name('superadmin.login')->middleware('guest');

    Route::post('/logout', function () {
        Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    })->name('superadmin.logout');

    Route::middleware(['auth'])->group(function () {
        Route::get('/tenants', App\Livewire\SuperAdmin\TenantsManager::class)->name('superadmin.tenants');
        Route::get('/admins', App\Livewire\SuperAdmin\AdminsManager::class)->name('superadmin.admins');
        Route::get('/billing', App\Livewire\SuperAdmin\BillingManager::class)->name('superadmin.billing');

        // Descarga segura de comprobantes de pago (solo SuperAdmin autenticado)
        Route::get('/receipts/{tenantId}/{filename}', function (string $tenantId, string $filename) {
            $tenantId = basename($tenantId);
            $filename = basename($filename);

            // Stancl Tenancy guarda los archivos en storage/tenant{id}/app/
            $absolutePath = storage_path("tenant{$tenantId}/app/receipts/{$tenantId}/{$filename}");

            abort_unless(file_exists($absolutePath), 404);

            return response()->file($absolutePath);
        })->name('superadmin.receipts');
    });
});
