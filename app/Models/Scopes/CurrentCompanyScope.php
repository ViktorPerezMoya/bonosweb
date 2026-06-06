<?php

namespace App\Models\Scopes;

use App\Services\CompanyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CurrentCompanyScope implements Scope
{
    /**
     * Aplica el scope a una query dada.
     * Solo actúa cuando hay un tenant activo y una empresa en contexto.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // No aplica fuera del contexto de un tenant
        if (!function_exists('tenant') || !tenant()) {
            return;
        }

        $companyId = app(CompanyContextService::class)->getCurrentCompanyId();

        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
