<?php

namespace App\Services;

use App\Models\Company;

class CompanyContextService
{
    private const SESSION_KEY = 'current_company_id';

    /**
     * Verifica si el usuario actual tiene una empresa válida en sesión.
     */
    public function hasValidContext(): bool
    {
        return $this->getCurrentCompanyId() !== null;
    }

    /**
     * Retorna el ID de la empresa activa en sesión.
     * Si no hay sesión, hace fallback a la empresa principal (is_main = true) o la primera accesible.
     */
    public function getCurrentCompanyId(): ?int
    {
        if ($id = session(self::SESSION_KEY)) {
            // Verificar si el usuario aún tiene acceso a la empresa en sesión
            $user = auth()->user();
            $hasAccess = true;
            if ($user && $user->role === 'employee') {
                $hasAccess = $user->companies()->where('companies.id', $id)->exists();
            } elseif ($user && $user->role === 'hr') {
                $hasAccess = $user->accessibleCompanies()->where('companies.id', $id)->exists();
            }
            if ($hasAccess) {
                return (int) $id;
            }
        }

        // Fallback
        if (!function_exists('tenant') || !tenant()) {
            return null;
        }

        $user = auth()->user();
        $fallbackId = null;

        if ($user && $user->role === 'employee') {
            $fallbackId = $user->companies()->where('employee_profiles.is_active', true)->where('companies.is_active', true)->orderByDesc('companies.is_main')->value('companies.id');
        } elseif ($user && $user->role === 'hr') {
            $fallbackId = $user->accessibleCompanies()->where('companies.is_active', true)->orderByDesc('companies.is_main')->value('companies.id');
        } else {
            $fallbackId = Company::where('is_main', true)->value('id') ?? Company::where('is_active', true)->value('id');
        }

        if ($fallbackId) {
            session()->put(self::SESSION_KEY, $fallbackId);
            return (int) $fallbackId;
        }

        return null;
    }

    /**
     * Establece la empresa activa en sesión.
     * Lanza una excepción si el ID no corresponde a una empresa del tenant.
     */
    public function setCurrentCompanyId(int $companyId): void
    {
        // Valida que la empresa exista en el tenant actual y esté activa
        $company = Company::findOrFail($companyId);
        abort_if(!$company->is_active, 403, 'La empresa seleccionada está inactiva.');

        $user = auth()->user();

        // Blindaje IDOR para Empleados:
        // Solo pueden seleccionar empresas donde poseen un legajo activo.
        if ($user && $user->role === 'employee') {
            abort_if(
                ! $user->companies()->where('employee_profiles.is_active', true)->where('companies.id', $companyId)->exists(),
                403,
                'No tienes acceso a esa empresa.'
            );
        }

        // Blindaje IDOR para RRHH:
        // Solo pueden seleccionar empresas a las que se les haya dado acceso explícito.
        if ($user && $user->role === 'hr') {
            abort_if(
                ! $user->accessibleCompanies()->where('companies.id', $companyId)->exists(),
                403,
                'No tienes permisos administrativos sobre esta empresa.'
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
