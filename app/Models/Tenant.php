<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $casts = [
        'cert_expiry' => 'date',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'employer_cuit',
            'is_suspended',
            'cert_path',
            'cert_key_path',
            'cert_expiry',
            'service_base_amount',
            'payment_day',
            'apply_inflation',
            'current_balance',
            // Configuración de firma visual del empleador
            'signature_x',
            'signature_y',
            'signature_w',
            'signature_h',
            'signature_image_path',
            'signature_preview_path',
            // Branding visual del tenant
            'logo_path',
            'login_background_path',
        ];
    }

    /** URL pública del logo del tenant, o null si no tiene uno configurado. */
    public function logoUrl(): ?string
    {
        if ($this->logo_path) {
            return route('branding.logo');
        }
        return null;
    }

    /** URL pública del fondo de login del tenant, o null si no tiene uno configurado. */
    public function loginBackgroundUrl(): ?string
    {
        if ($this->login_background_path) {
            return route('branding.background');
        }
        return null;
    }

    public function globalUsers()
    {
        return $this->belongsToMany(GlobalUser::class, 'global_user_tenant');
    }

    public function invoices()
    {
        return $this->hasMany(TenantInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(TenantPayment::class);
    }
}
