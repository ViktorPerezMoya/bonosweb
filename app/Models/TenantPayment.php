<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPayment extends Model
{
    /**
     * Los datos de pagos viven en la BD central, no en la BD de cada tenant.
     * Esta propiedad garantiza que el modelo siempre consulte la BD correcta,
     * incluso cuando se accede desde dentro del contexto de un tenant.
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'tenant_id',
        'amount',
        'receipt_path',
        'payment_date',
        'status',
        'reported_by_user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
