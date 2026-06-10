<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CompanyContextService;

class EnsureValidCompanyContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app(CompanyContextService::class)->hasValidContext()) {
            // Empleados sin empresas válidas deben poder llegar a /mis-bonos para ver el "Empty State"
            if (auth()->check() && auth()->user()->role === 'employee' && $request->routeIs('employee.my-payslips')) {
                return $next($request);
            }

            return redirect()->route('dashboard')->with('error', 'Debes tener una empresa seleccionada para acceder a este módulo.');
        }

        return $next($request);
    }
}
