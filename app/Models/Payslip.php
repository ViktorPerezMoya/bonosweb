<?php

namespace App\Models;

use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope());
    }

    protected $fillable = [
        'employee_id',
        'company_id',
        'upload_batch_id',
        'period_year',
        'period_month',
        'liquidation_type',
        'file_path',
        'original_filename',
        'file_hash',
        'status',
        'signed_at',
        'is_rectified',
        'rectified_by_id',
        'disagreement_reason_id',
        'disconformity_reason',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadBatch()
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function signature()
    {
        return $this->hasOne(Signature::class);
    }

    public function disagreementReason()
    {
        return $this->belongsTo(DisagreementReason::class, 'disagreement_reason_id');
    }
}
