<?php

namespace App\Livewire\Employees;

use App\Models\Payslip;
use App\Models\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vista de empleado: muestra únicamente los recibos de sueldo propios.
 *
 * Accesible con rol 'employee' (redirigido desde Login).
 * No muestra recibos de otros empleados; no necesita CompanyContextService
 * porque el empleado ve sus recibos de TODAS las empresas del tenant.
 */
class MisBonos extends Component
{
    use WithPagination;

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\Contracts\View\View
    {
        $payslips = Payslip::where('employee_id', Auth::id())
            ->where('is_rectified', false)       // Ocultar los que fueron reemplazados
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(15);

        return view('livewire.employees.mis-bonos', compact('payslips'))
            ->layout('components.layouts.app');
    }
}
