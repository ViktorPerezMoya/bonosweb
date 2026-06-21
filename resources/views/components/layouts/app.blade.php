<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BonosWeb - Panel RRHH' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    @livewireStyles
    <script>
        if (localStorage.theme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
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

            <livewire:layouts.sidebar />

        </aside>

        {{-- Main --}}
        <main class="flex flex-col flex-1 min-h-screen min-w-0 md:ml-64">

            {{-- Top Header --}}
            @php
            $routeTitles = [
                'dashboard'             => ['icon' => 'ri-dashboard-line',            'label' => 'Dashboard'],
                'mis-bonos'             => ['icon' => 'ri-file-text-line',             'label' => 'Mis Bonos'],
                'employees'             => ['icon' => 'ri-team-line',                  'label' => 'Gestión de Empleados'],
                'employees/*/history'   => ['icon' => 'ri-file-history-line',          'label' => 'Historial del Empleado'],
                'payslips/upload'       => ['icon' => 'ri-upload-cloud-2-line',        'label' => 'Subir Recibos'],
                'payslips/list'         => ['icon' => 'ri-list-check-2',               'label' => 'Lotes Procesados'],
                'reports/signatures'    => ['icon' => 'ri-shield-check-line',          'label' => 'Auditoría de Firmas'],
                'reports/disconformities'=> ['icon' => 'ri-error-warning-line',         'label' => 'Auditoría de Disconformidades'],
                'users'                 => ['icon' => 'ri-user-settings-line',         'label' => 'Usuarios RRHH'],
                'configuracion/firma'   => ['icon' => 'ri-pen-nib-line',               'label' => 'Firma Empleador'],
                'configuracion/certificado-raiz'=> ['icon' => 'ri-shield-keyhole-line',     'label' => 'Certificado Raíz'],
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
                <h2 class="m-0 text-base font-bold md:text-lg" style="letter-spacing: -0.01em; color: var(--text-primary);">{{ $pageLabel }}</h2>

                <div class="ml-auto flex items-center gap-2 shrink-0">
                    {{-- Toggle Tema --}}
                    <div x-data="{ isDark: document.documentElement.classList.contains('dark') }">
                        <button @click="isDark = !isDark; isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'); localStorage.theme = isDark ? 'dark' : 'light'"
                                class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
                                :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'">
                            <i :class="isDark ? 'ri-sun-line text-yellow-400' : 'ri-moon-line text-slate-700'" class="text-xl"></i>
                        </button>
                    </div>

                    {{-- Company Switcher (solo si hay tenant activo con múltiples empresas) --}}
                    @if(function_exists('tenant') && tenant())
                    <livewire:tenant.company-switcher />
                    @endif
                </div>
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
