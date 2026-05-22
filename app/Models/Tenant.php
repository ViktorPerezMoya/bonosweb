<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'is_suspended',
            'cert_path',
            'cert_key_path',
            'service_base_amount',
            'payment_day',
            'apply_inflation',
            'current_balance',
        ];
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
