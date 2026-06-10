<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Tenant;
use App\Models\Company;
use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Storage;

echo "Iniciando migración de certificados...\n";

$tenants = Tenant::all();

foreach ($tenants as $tenant) {
    tenancy()->initialize($tenant);
    
    $companies = Company::withoutGlobalScope(CurrentCompanyScope::class)->get();
    
    foreach ($companies as $company) {
        $oldPath = $company->signature_pfx_path;
        
        if ($oldPath && str_starts_with($oldPath, 'certs/company_')) {
            $newPath = str_replace('certs/company_', 'certs/companies/company_', $oldPath);
            
            // Si el archivo todavía está en la ruta vieja, lo movemos
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->makeDirectory('certs/companies');
                Storage::disk('local')->move($oldPath, $newPath);
                echo "Archivo movido: {$oldPath} -> {$newPath} en Tenant {$tenant->id}\n";
            }
            
            // Actualizamos la base de datos incondicionalmente
            $company->update(['signature_pfx_path' => $newPath]);
            echo "Base de datos actualizada: {$oldPath} -> {$newPath} en Tenant {$tenant->id}\n";
        }
    }
    
    tenancy()->end();
}

echo "Migración completada.\n";
