<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SuperAdmin - BonosWeb' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    @livewireStyles
    <style>
        :root {
            --accent: #8b5cf6;
            --accent-hover: #7c3aed;
        }

        /* El sidebar recibe su ancho w-64 de Tailwind; este bloque solo maneja
           color, blur y borde para mantener la coherencia "frosted glass".      */
        .super-sidebar {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
        }

        .super-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: white;
        }

        .super-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            gap: 10px;
        }

        .super-nav-item:hover,
        .super-nav-item.active {
            background: rgba(139, 92, 246, 0.15);
            color: white;
            border-right: 3px solid var(--accent);
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>

{{-- Alpine: sidebarOpen controla la visibilidad del sidebar en móvil --}}
<body x-data="{ sidebarOpen: false }">

    @auth

        {{-- ── Overlay oscuro: cierra el sidebar al tocar fuera (solo móvil) ── --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 md:hidden"
        ></div>

        {{-- ── Sidebar: oculto en móvil (translate-x), siempre visible en md+ ── --}}
        <aside
            class="super-sidebar fixed top-0 left-0 h-full w-64 z-40 flex flex-col
                   transition-transform duration-300 ease-in-out"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            {{-- Brand --}}
            <div class="flex items-center gap-2 px-6 py-5 border-b border-white/10">
                <span class="super-brand text-xl flex items-center gap-2">
                    <i class="ri-global-line" style="color: var(--accent);"></i> Central Admin
                </span>
            </div>

            {{-- Navegación --}}
            <nav class="flex-1 py-3">
                <a href="{{ route('superadmin.tenants') }}"
                   class="super-nav-item {{ request()->routeIs('superadmin.tenants') ? 'active' : '' }}">
                    <i class="ri-building-4-line"></i> Empresas (Tenants)
                </a>
                <a href="{{ route('superadmin.admins') }}"
                   class="super-nav-item {{ request()->routeIs('superadmin.admins') ? 'active' : '' }}">
                    <i class="ri-admin-line"></i> Administradores
                </a>
                <div class="mx-6 my-2 border-t border-white/10"></div>
                <a href="{{ route('superadmin.billing') }}"
                   class="super-nav-item {{ request()->routeIs('superadmin.billing') ? 'active' : '' }}">
                    <i class="ri-money-dollar-circle-line"></i> Cobros y Facturación
                </a>
            </nav>

            {{-- Usuario + Logout --}}
            <div class="px-5 py-4 border-t border-white/10">
                <p class="text-xs mb-3" style="color: var(--text-secondary);">
                    Logueado como:<br>
                    <strong class="text-white">{{ auth()->user()->name }}</strong>
                </p>
                <form method="POST" action="{{ route('superadmin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-logout w-full text-sm">
                        <i class="ri-logout-box-r-line"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Navbar superior (solo visible en móvil) ───────────────────────── --}}
        <header
            class="md:hidden fixed top-0 inset-x-0 z-20
                   flex items-center justify-between px-4 py-3"
            style="background: rgba(15, 23, 42, 0.9);
                   backdrop-filter: blur(20px);
                   -webkit-backdrop-filter: blur(20px);
                   border-bottom: 1px solid var(--glass-border);"
        >
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="w-10 h-10 flex items-center justify-center rounded-lg
                       hover:bg-white/10 transition-colors"
                aria-label="Abrir menú"
            >
                <i class="ri-menu-line text-xl text-white"></i>
            </button>

            <span class="super-brand text-base flex items-center gap-2">
                <i class="ri-global-line" style="color: var(--accent);"></i> Central Admin
            </span>

            {{-- Espaciador simétrico para que el brand quede centrado --}}
            <div class="w-10" aria-hidden="true"></div>
        </header>

        {{-- ── Contenido principal ────────────────────────────────────────────── --}}
        {{-- pt-16 compensa el navbar fijo en móvil; md:pt-0 lo elimina en desktop --}}
        <main class="md:ml-64 pt-16 md:pt-0 p-4 md:p-8 min-h-screen">

            @if(isset($header))
                <div class="glass-panel mb-6" style="padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-top: 1rem;">
                    <h1 class="text-xl md:text-2xl font-bold text-white" style="margin: 0;">{{ $header }}</h1>
                </div>
            @endif

            @if (session()->has('message'))
                <div class="alert alert-success mb-6">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-error mb-6">{{ session('error') }}</div>
            @endif

            {{ $slot }}

        </main>

    @else
        {{-- Layout mínimo para la pantalla de login del SuperAdmin --}}
        <main class="flex items-center justify-center min-h-screen">
            {{ $slot }}
        </main>
    @endauth

    @livewireScripts
</body>
</html>
