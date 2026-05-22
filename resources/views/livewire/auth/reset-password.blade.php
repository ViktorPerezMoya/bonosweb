<div class="flex-center min-h-screen">
    <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="ri-key-2-line" style="font-size: 3rem; color: var(--accent);"></i>
            <h2 style="margin-top: 1rem;">Nueva Contraseña</h2>
            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.5rem;">Por favor ingresa tu nueva contraseña para acceder al sistema.</p>
        </div>

        <form wire:submit.prevent="resetPassword">
            <input type="hidden" wire:model="token">

            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" id="email" wire:model="email" class="form-control" required readonly style="opacity: 0.7; cursor: not-allowed;">
                @error('email') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 5px;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Nueva Contraseña</label>
                <input type="password" id="password" wire:model="password" class="form-control" placeholder="••••••••" required autofocus>
                @error('password') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 5px;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input type="password" id="password_confirmation" wire:model="password_confirmation" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <span wire:loading.remove wire:target="resetPassword">Guardar Contraseña</span>
                <span wire:loading wire:target="resetPassword">Guardando...</span>
            </button>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem;">
                <a href="{{ route('login') }}" style="color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <i class="ri-arrow-left-line"></i> Cancelar y volver
                </a>
            </div>
        </form>
    </div>
</div>
