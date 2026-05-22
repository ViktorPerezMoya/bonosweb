<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class GlobalUser extends Authenticatable
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'cuil',
        'dni',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'global_user_tenant');
    }
}
