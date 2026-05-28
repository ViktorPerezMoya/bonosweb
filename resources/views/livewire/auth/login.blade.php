<div class="flex min-h-screen items-center justify-center
            {{ $bgUrl ? 'bg-cover bg-center bg-no-repeat' : '' }}"
     @if($bgUrl) style="background-image: url('{{ $bgUrl }}'); width: 100%; height: 100vh;" @endif>
    <div class="glass-panel {{ $bgUrl ? 'backdrop-blur-sm' : '' }} mx-4"
         style="width: 100%; max-width: 400px; padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="ri-wallet-3-line" style="font-size: 3rem; color: var(--accent);"></i>
            <h2 style="margin-top: 1rem;">Acceso RRHH</h2>
            <p>Sistema de Gestión de Bonos</p>
        </div>

        @if (session()->has('message'))
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center; font-size: 0.85rem;">
                {{ session('message') }}
            </div>
        @endif

        <form wire:submit.prevent="login">
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" id="email" wire:model="email" class="form-control" placeholder="rrhh@empresa.com" required>
                @error('email') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 5px;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" wire:model="password" class="form-control" placeholder="••••••••" required>
                @error('password') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 5px;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px; margin-bottom: 0;">
                    <input type="checkbox" id="remember" wire:model="remember">
                    <label for="remember" style="color: var(--text-secondary); font-size: 0.85rem; cursor: pointer;">Recordar sesión</label>
                </div>

                <a href="{{ route('password.request') }}" style="color: var(--accent); font-size: 0.85rem; text-decoration: none; transition: opacity 0.2s;margin-left: 10px;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="login" style="width: 100%; margin-top: 1rem; justify-content: center;">
                <span wire:loading.remove wire:target="login">
                    Ingresar al Sistema
                </span>
                <span wire:loading wire:target="login">
                    <i class="ri-loader-4-line" style="display: inline-block; animation: spin 0.8s linear infinite; margin-right: 6px;"></i>Verificando...
                </span>
            </button>
        </form>
    </div>
</div>
