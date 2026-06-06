<?php

namespace App\Models;

use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope());
    }

    protected $fillable = [
        'user_id',
        'company_id',
        'cuil',
        'document_number',
        'file_number',
        'department',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
