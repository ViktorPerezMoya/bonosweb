<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('universal', []);

        // Alias para proteger rutas exclusivas de administradores tenant y contexto válido.
        $middleware->alias([
            'tenant.admin' => \App\Http\Middleware\EnsureTenantAdmin::class,
            'tenant.context' => \App\Http\Middleware\EnsureValidCompanyContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cuando la sesión del SuperAdmin expira, redirigir al login correcto
        // en vez del default 'login' (que no existe y devuelve 404)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if (str_starts_with($request->path(), 'superadmin')) {
                return redirect()->route('superadmin.login')
                    ->with('status', 'Tu sesión ha expirado. Ingresá nuevamente.');
            }
        });
    })->create();
