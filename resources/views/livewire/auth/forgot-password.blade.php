<div class="flex-center min-h-screen">
    <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="ri-lock-password-line" style="font-size: 3rem; color: var(--accent);"></i>
            <h2 style="margin-top: 1rem;">Recuperar Contraseña</h2>
            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.5rem;">Ingresa tu correo y te enviaremos un enlace para restablecer tu clave.</p>
        </div>

        @if (session('status'))
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem;">
                <i class="ri-checkbox-circle-line" style="font-size: 1.2rem;"></i>
                <div>Enlace enviado. Por favor revisa tu bandeja de entrada o la carpeta de spam.</div>
            </div>
        @endif

        <form wire:submit.prevent="sendResetLink">
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" id="email" wire:model="email" class="form-control" placeholder="rrhh@empresa.com" required autofocus>
                @error('email') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 5px;">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <span wire:loading.remove wire:target="sendResetLink">Enviar Enlace</span>
                <span wire:loading wire:target="sendResetLink">Enviando...</span>
            </button>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem;">
                <a href="{{ route('login') }}" style="color: var(--text-secondary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <i class="ri-arrow-left-line"></i> Volver al Acceso
                </a>
            </div>
        </form>
    </div>
</div>
