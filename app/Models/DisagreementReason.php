<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CurrentCompanyScope;

class DisagreementReason extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope());
    }

    protected $fillable = [
        'company_id',
        'reason_text',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
