<div class="flex flex-col h-full">
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-6 pt-8 pb-8 text-xl font-bold">
        @if($companyLogoUrl)
            @if($hideNameInMenu)
                <img src="{{ $companyLogoUrl }}" alt="{{ $companyName }}"
                     class="h-10 w-auto object-contain" style="max-width: 100%;">
            @else
                <img src="{{ $companyLogoUrl }}" alt="{{ $companyName }}"
                     class="h-8 w-8 object-contain shrink-0">
                <span class="truncate text-ellipsis">{{ $companyName }}</span>
            @endif
        @else
            <i class="ri-wallet-3-line text-accent shrink-0"></i>
            <span class="truncate text-ellipsis">{{ $companyName ?? 'BonosWeb' }}</span>
        @endif
    </div>

    {{-- Nav Links --}}
    <ul class="flex-1 overflow-y-auto px-4 space-y-1" style="list-style:none; margin:0; padding-left:1rem; padding-right:1rem;">

        @if(auth()->user()->role === 'employee')
        {{-- ── Menú Empleado (mínimo: solo sus bonos y su perfil) ──── --}}
        <li><a href="/mis-bonos" class="nav-link {{ request()->is('mis-bonos') ? 'active' : '' }}"><i class="ri-file-text-line"></i> Mis Bonos</a></li>

        @else
        {{-- ── Menú Admin / RRHH ─────────────────────────────────────── --}}
        <li><a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"><i class="ri-dashboard-line"></i> Dashboard</a></li>
        <li><a href="/employees" class="nav-link {{ request()->is('employees') ? 'active' : '' }}"><i class="ri-team-line"></i> Empleados</a></li>
        <li><a href="/payslips/upload" class="nav-link {{ request()->is('payslips/upload') ? 'active' : '' }}"><i class="ri-upload-cloud-2-line"></i> Subir Recibos</a></li>
        <li><a href="/payslips/list" class="nav-link {{ request()->is('payslips/list') ? 'active' : '' }}"><i class="ri-list-check-2"></i> Lotes Procesados</a></li>
        <li><a href="/reports/signatures" class="nav-link {{ request()->is('reports/signatures') ? 'active' : '' }}"><i class="ri-shield-check-line"></i> Auditoría de Firmas</a></li>
        <li class="pt-2"><div class="border-t border-white/10"></div></li>
        <li><a href="/configuracion/certificado-raiz" class="nav-link {{ request()->is('configuracion/certificado-raiz') ? 'active' : '' }}"><i class="ri-shield-keyhole-line"></i> Certificado Raíz</a></li>
        @if(auth()->user()->role === 'admin')
        <li><a href="/users" class="nav-link {{ request()->is('users') ? 'active' : '' }}"><i class="ri-user-settings-line"></i> Usuarios RRHH</a></li>
        <li><a href="/empresas" class="nav-link {{ request()->is('empresas') ? 'active' : '' }}"><i class="ri-building-4-line"></i> Empresas</a></li>
        <li><a href="/configuracion/firma" class="nav-link {{ request()->is('configuracion/firma') ? 'active' : '' }}"><i class="ri-pen-nib-line"></i> Firma Empleador</a></li>
        <li><a href="/configuracion/branding" class="nav-link {{ request()->is('configuracion/branding') ? 'active' : '' }}"><i class="ri-palette-line"></i> Identidad Visual</a></li>
        <li><a href="/configuracion/motivos-disconformidad" class="nav-link {{ request()->is('configuracion/motivos-disconformidad') ? 'active' : '' }}"><i class="ri-question-answer-line"></i> Motivos Disconformidad</a></li>
        @endif
        <li class="pt-2"><div class="border-t border-white/10"></div></li>
        @if(auth()->user()->role === 'admin')
        <li><a href="/billing" class="nav-link {{ request()->is('billing') ? 'active' : '' }}"><i class="ri-money-dollar-circle-line"></i> Mi Cuenta</a></li>
        @endif

        @endif

        {{-- ── Mi Perfil y Logout (todos los roles) ────────────────── --}}
        <li>
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
</div>
