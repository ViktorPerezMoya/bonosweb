<?php

namespace App\Services;

use App\Models\Company;

class CompanyContextService
{
    private const SESSION_KEY = 'current_company_id';

    /**
     * Retorna el ID de la empresa activa en sesión.
     * Si no hay sesión, hace fallback a la empresa principal (is_main = true).
     */
    public function getCurrentCompanyId(): ?int
    {
        if ($id = session(self::SESSION_KEY)) {
            return (int) $id;
        }

        // Fallback: empresa principal del tenant
        if (!function_exists('tenant') || !tenant()) {
            return null;
        }

        $mainId = Company::where('is_main', true)->value('id');
        if ($mainId) {
            session()->put(self::SESSION_KEY, $mainId);
        }

        return $mainId ? (int) $mainId : null;
    }

    /**
     * Establece la empresa activa en sesión.
     * Lanza una excepción si el ID no corresponde a una empresa del tenant.
     */
    public function setCurrentCompanyId(int $companyId): void
    {
        // Valida que la empresa exista en el tenant actual
        Company::findOrFail($companyId);

        // Blindaje IDOR: un empleado solo puede seleccionar empresas donde
        // posee un legajo activo en employee_profiles. Cualquier intento de
        // forzar un companyId ajeno se rechaza con 403 (Forbidden).
        $user = auth()->user();
        if ($user && $user->role === 'employee') {
            abort_if(
                ! $user->companies()->where('id', $companyId)->exists(),
                403,
                'No tienes acceso a esa empresa.'
            );
        }

        session()->put(self::SESSION_KEY, $companyId);
    }

    /**
     * Limpia la empresa activa de la sesión (vuelve al fallback is_main).
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
