<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'BonosWeb - Panel RRHH' }}</title>
    <link rel="stylesheet" href="{{ global_asset('css/app.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    @livewireStyles
</head>
<body>
    @auth
        <nav class="sidebar glass-panel">
            <div class="logo">
                <i class="ri-wallet-3-line"></i> BonosWeb
            </div>
            <ul class="nav-links">
                <li><a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="ri-dashboard-line"></i> Dashboard</a></li>
                <li><a href="/employees" class="{{ request()->is('employees') ? 'active' : '' }}"><i class="ri-team-line"></i> Empleados</a></li>
                <li><a href="/payslips/upload" class="{{ request()->is('payslips/upload') ? 'active' : '' }}"><i class="ri-upload-cloud-2-line"></i> Subir Recibos</a></li>
                <li><a href="/payslips/list" class="{{ request()->is('payslips/list') ? 'active' : '' }}"><i class="ri-list-check-2"></i> Lotes Procesados</a></li>
                <li><a href="/reports/signatures" class="{{ request()->is('reports/signatures') ? 'active' : '' }}"><i class="ri-shield-check-line"></i> Auditoría de Firmas</a></li>
                @if(auth()->user()->role === 'admin')
                <li><a href="/users" class="{{ request()->is('users') ? 'active' : '' }}"><i class="ri-user-settings-line"></i> Usuarios RRHH</a></li>
                @endif
                <li style="margin-top: 0.5rem; padding: 0 0.5rem;"><div style="border-top: 1px solid var(--glass-border);"></div></li>
                <li><a href="/billing" class="{{ request()->is('billing') ? 'active' : '' }}"><i class="ri-money-dollar-circle-line"></i> Mi Cuenta</a></li>
            </ul>
            <div class="user-info">
                <a href="{{ route('profile.show') }}" class="user-profile-link">
                    <i class="ri-user-line"></i> {{ auth()->user()->name }}
                </a>
                <!-- Logout funcional -->
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 10px;">
                    @csrf
                    <button type="submit" class="btn btn-logout" style="width: 100%; font-size: 0.85rem;">
                        <i class="ri-logout-box-r-line"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </nav>
        
        <main class="main-content">
            <header class="top-header glass-panel">
                <h2>{{ $header ?? 'Panel Administrativo' }}</h2>
            </header>
            
            <div class="content-wrapper">
                {{ $slot }}
            </div>
        </main>
    @else
        <div class="guest-wrapper">
            {{ $slot }}
        </div>
    @endauth

    @livewireScripts
    
    <style>
        /* Layout specific styles */
        .guest-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
        }
        body {
            flex-direction: row;
        }
        
        .sidebar {
            width: 260px;
            height: 100vh;
            border-radius: 0;
            border-top: none;
            border-bottom: none;
            border-left: none;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
            z-index: 10;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i { color: var(--accent); }
        
        .nav-links {
            list-style: none;
            flex-grow: 1;
        }
        
        .nav-links li { margin-bottom: 0.5rem; }
        
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent);
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            padding-top: 1rem;
            border-top: 1px solid var(--glass-border);
            font-size: 0.9rem;
        }

        .user-profile-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            transition: all 0.2s;
            font-weight: 500;
        }

        .user-profile-link:hover {
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent);
        }
        
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .top-header {
            margin: 2rem 2rem 0;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .content-wrapper {
            padding: 2rem;
            flex-grow: 1;
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

        .btn-csv {
            background-color: rgba(183, 249, 142, 0.1);
            color: var(--success);
            transition: all 0.2s ease;
        }

        .btn-csv:hover {
            background-color: rgba(23, 226, 22, 0.32);
            box-shadow: 0 4px 12px rgba(183, 249, 142, 0.1);
            transform: translateY(-1px);
        }
    </style>
</body>
</html>
