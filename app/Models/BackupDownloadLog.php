<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupDownloadLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
