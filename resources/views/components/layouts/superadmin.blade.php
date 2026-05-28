<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SuperAdmin - BonosWeb' }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Global Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @livewireStyles
    <style>
        /* Specific tweaks for SuperAdmin theme (a bit more "dark purple/gold" to distinguish it) */
        :root {
            --accent: #8b5cf6; /* Purple for central admin */
            --accent-hover: #7c3aed;
        }

        .super-sidebar {
            width: 250px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            border-right: 1px solid var(--glass-border);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
        }

        .super-brand {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
            margin-bottom: 1.5rem;
        }

        .super-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
        }

        .super-nav-item:hover, .super-nav-item.active {
            background: rgba(139, 92, 246, 0.15);
            color: white;
            border-right: 3px solid var(--accent);
        }

        .super-main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
            min-height: 100vh;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    @auth
        <aside class="super-sidebar">
            <div class="super-brand">
                <i class="ri-global-line" style="color: var(--accent);"></i> Central Admin
            </div>

            <nav style="flex-grow: 1;">
                <a href="{{ route('superadmin.tenants') }}" class="super-nav-item {{ request()->routeIs('superadmin.tenants') ? 'active' : '' }}">
                    <i class="ri-building-4-line"></i> Empresas (Tenants)
                </a>
                <a href="{{ route('superadmin.admins') }}" class="super-nav-item {{ request()->routeIs('superadmin.admins') ? 'active' : '' }}">
                    <i class="ri-admin-line"></i> Administradores
                </a>
                <div style="margin: 0.75rem 1.5rem; border-top: 1px solid var(--glass-border);"></div>
                <a href="{{ route('superadmin.billing') }}" class="super-nav-item {{ request()->routeIs('superadmin.billing') ? 'active' : '' }}">
                    <i class="ri-money-dollar-circle-line"></i> Cobros y Facturación
                </a>
            </nav>

            <div style="padding: 1.5rem; border-top: 1px solid var(--glass-border);">
                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    Logueado como:<br>
                    <strong style="color: white;">{{ auth()->user()->name }}</strong>
                </div>
                <form method="POST" action="{{ route('superadmin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-logout" style="width: 100%; font-size: 0.85rem;">
                        <i class="ri-logout-box-r-line"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="super-main-content">
            @if(isset($header))
                <header style="margin-bottom: 2rem;">
                    <h1 style="font-size: 1.8rem; font-weight: 700; color: white;">{{ $header }}</h1>
                </header>
            @endif

            @if (session()->has('message'))
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    {{ session('message') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    @else
        <!-- Login View layout -->
        <main style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
            {{ $slot }}
        </main>
    @endauth

    @livewireScripts
</body>
</html>
