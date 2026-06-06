<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario autenticado posea el rol "admin" dentro del tenant.
 *
 * Forma parte de la estrategia de Seguridad en Capas:
 *   1. Este middleware bloquea el acceso directo por URL (capa de ruta).
 *   2. Los componentes Livewire repiten la verificación en mount() (capa de componente).
 *   3. El sidebar oculta los enlaces para roles no admin (capa de UX).
 */
class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            if ($request->expectsJson()) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
