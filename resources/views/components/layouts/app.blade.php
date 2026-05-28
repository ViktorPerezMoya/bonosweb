<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BonosWeb - Panel RRHH' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    @livewireStyles
</head>
<body>
    @auth
    <div x-data="{ sidebarOpen: false }" class="flex min-h-screen overflow-x-hidden">

        {{-- Overlay móvil --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-20 bg-black/50 md:hidden"
             style="display:none;"></div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
               class="sidebar-nav fixed top-0 left-0 z-30 flex h-screen w-64 flex-col
                      transform transition-transform duration-300 ease-in-out">

            {{-- Logo --}}
            @php $tenantLogoUrl = (function_exists('tenant') && tenant()) ? tenant()->logoUrl() : null; @endphp
            <div class="flex items-center gap-2.5 px-6 pt-8 pb-8 text-xl font-bold">
                @if($tenantLogoUrl)
                    <img src="{{ $tenantLogoUrl }}" alt="{{ tenant('company_name') }}"
                         class="h-12 w-auto object-contain" style="max-width: 160px;">
                    {{ tenant('company_name') }}
                @else
                    <i class="ri-wallet-3-line text-accent"></i>
                    <span>BonosWeb</span>
                @endif
            </div>

            {{-- Nav Links --}}
            <ul class="flex-1 overflow-y-auto px-4 space-y-1" style="list-style:none; margin:0; padding-left:1rem; padding-right:1rem;">
                <li><a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"><i class="ri-dashboard-line"></i> Dashboard</a></li>
                <li><a href="/employees" class="nav-link {{ request()->is('employees') ? 'active' : '' }}"><i class="ri-team-line"></i> Empleados</a></li>
                <li><a href="/payslips/upload" class="nav-link {{ request()->is('payslips/upload') ? 'active' : '' }}"><i class="ri-upload-cloud-2-line"></i> Subir Recibos</a></li>
                <li><a href="/payslips/list" class="nav-link {{ request()->is('payslips/list') ? 'active' : '' }}"><i class="ri-list-check-2"></i> Lotes Procesados</a></li>
                <li><a href="/reports/signatures" class="nav-link {{ request()->is('reports/signatures') ? 'active' : '' }}"><i class="ri-shield-check-line"></i> Auditoría de Firmas</a></li>
                @if(auth()->user()->role === 'admin')
                <li><a href="/users" class="nav-link {{ request()->is('users') ? 'active' : '' }}"><i class="ri-user-settings-line"></i> Usuarios RRHH</a></li>
                <li><a href="/configuracion/firma" class="nav-link {{ request()->is('configuracion/firma') ? 'active' : '' }}"><i class="ri-pen-nib-line"></i> Firma Empleador</a></li>
                <li><a href="/configuracion/branding" class="nav-link {{ request()->is('configuracion/branding') ? 'active' : '' }}"><i class="ri-palette-line"></i> Identidad Visual</a></li>
                @endif
                <li class="pt-2"><div class="border-t border-white/10"></div></li>
                <li><a href="/billing" class="nav-link {{ request()->is('billing') ? 'active' : '' }}"><i class="ri-money-dollar-circle-line"></i> Mi Cuenta</a></li>
                <li>
                    {{-- Usuario --}}
                    <div class="pb-6 pt-4 border-t border-white/10">
                        <a href="{{ route('profile.show') }}" class="nav-link mb-1">
                            <i class="ri-user-line"></i> {{ auth()->user()->name }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-logout w-full text-sm" style="padding: 0.6rem 1rem;">
                                <i class="ri-logout-box-r-line" style="margin-right:6px;"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </li>
            </ul>

        </aside>

        {{-- Main --}}
        <main class="flex flex-col flex-1 min-h-screen min-w-0 md:ml-64">

            {{-- Top Header --}}
            @php
            $routeTitles = [
                'dashboard'             => ['icon' => 'ri-dashboard-line',            'label' => 'Dashboard'],
                'employees'             => ['icon' => 'ri-team-line',                  'label' => 'Gestión de Empleados'],
                'employees/*/history'   => ['icon' => 'ri-file-history-line',          'label' => 'Historial del Empleado'],
                'payslips/upload'       => ['icon' => 'ri-upload-cloud-2-line',        'label' => 'Subir Recibos'],
                'payslips/list'         => ['icon' => 'ri-list-check-2',               'label' => 'Lotes Procesados'],
                'reports/signatures'    => ['icon' => 'ri-shield-check-line',          'label' => 'Auditoría de Firmas'],
                'users'                 => ['icon' => 'ri-user-settings-line',         'label' => 'Usuarios RRHH'],
                'configuracion/firma'   => ['icon' => 'ri-pen-nib-line',               'label' => 'Firma Empleador'],
                'configuracion/branding'=> ['icon' => 'ri-palette-line',                'label' => 'Identidad Visual'],
                'billing'               => ['icon' => 'ri-money-dollar-circle-line',   'label' => 'Mi Cuenta'],
                'profile'               => ['icon' => 'ri-user-line',                  'label' => 'Mi Perfil'],
            ];
            $pageInfo = collect($routeTitles)->first(fn($v, $k) => request()->is($k));
            $pageLabel = $pageInfo['label'] ?? ($header ?? 'Panel');
            $pageIcon  = $pageInfo['icon']  ?? 'ri-apps-line';
            @endphp
            <header class="glass-panel flex items-center gap-3 mx-3 mt-3 mb-0 md:mx-8 md:mt-6"
                    style="border-radius: var(--radius-md); padding: 0.75rem 1.25rem;">
                {{-- Hamburger (solo móvil) --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-white/10 transition-colors shrink-0"
                        aria-label="Menú">
                    <i class="ri-menu-line text-xl"></i>
                </button>
                <i class="{{ $pageIcon }} text-accent text-xl shrink-0 hidden md:inline-block"></i>
                <h2 class="m-0 text-base font-bold text-white md:text-lg" style="letter-spacing: -0.01em;">{{ $pageLabel }}</h2>
            </header>

            {{-- Contenido de página --}}
            <div class="p-4 md:p-8 flex-1">
                {{ $slot }}
            </div>
        </main>
    </div>

    @else
        <div class="guest-wrapper">
            {{ $slot }}
        </div>
    @endauth

    @livewireScripts
</body>
</html>
