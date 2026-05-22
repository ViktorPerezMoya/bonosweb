<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantInvoice extends Model
{
    /**
     * Los datos de facturación viven en la BD central, no en la BD de cada tenant.
     * Esta propiedad garantiza que el modelo siempre consulte la BD correcta,
     * incluso cuando se accede desde dentro del contexto de un tenant.
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'tenant_id',
        'period_month',
        'period_year',
        'amount',
        'due_date',
        'status',
        'pdf_file_path',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount'   => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
