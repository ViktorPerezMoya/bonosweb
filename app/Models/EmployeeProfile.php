<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
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
}
