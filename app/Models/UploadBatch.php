<?php

namespace App\Models;

use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\Model;

class UploadBatch extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope());
    }

    protected $fillable = [
        'uploader_id',
        'company_id',
        'original_filename',
        'file_type',
        'period_year',
        'period_month',
        'liquidation_type',
        'notification_date',
        'notifications_sent',
        'status',
        'total_files',
        'processed_files',
        'error_log',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }
}
