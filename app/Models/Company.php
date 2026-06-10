<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'cuit',
        'is_main',
        'signature_pfx_path',
        'signature_pfx_password',
        'signature_pfx_expires_at',
        'signature_x',
        'signature_y',
        'signature_w',
        'signature_h',
        'signature_image_path',
        'signature_preview_path',
        'signature_anchor_text',
        'signature_anchor_offset_y',
        'signature_page_w',
        'signature_page_h',
        'logo_path',
        'hide_name_in_menu',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main'                    => 'boolean',
            'hide_name_in_menu'          => 'boolean',
            'signature_pfx_expires_at'   => 'date',
            'signature_x'                => 'float',
            'signature_y'                => 'float',
            'signature_w'                => 'float',
            'signature_h'                => 'float',
            'signature_anchor_offset_y'  => 'float',
            'signature_page_w'           => 'float',
            'signature_page_h'           => 'float',
            'is_active'                  => 'boolean',
        ];
    }

    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function uploadBatches()
    {
        return $this->hasMany(UploadBatch::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }
}
