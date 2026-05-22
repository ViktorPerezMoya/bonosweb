<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    protected $fillable = [
        'payslip_id',
        'user_id',
        'pdf_hash',
        'ip_address',
        'device_info',
        'signed_at',
    ];

    public $timestamps = true;

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
