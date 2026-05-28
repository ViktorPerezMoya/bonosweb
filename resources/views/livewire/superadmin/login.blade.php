<div class="glass-panel" style="width: 100%; max-width: 400px;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <i class="ri-shield-keyhole-line" style="font-size: 3rem; color: var(--accent);"></i>
        <h2 style="font-family: 'Outfit', sans-serif; color: white; margin-top: 0.5rem;">Acceso Central</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Solo personal autorizado</p>
    </div>

    <form wire:submit.prevent="login">
        <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" wire:model="email" class="form-control" placeholder="admin@bonosweb.com" required>
            @error('email') <span style="color: var(--danger); font-size: 0.85rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Contraseña</label>
            <input type="password" wire:model="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="login" style="width: 100%; justify-content: center; font-size: 1rem; padding: 0.75rem;">
            <span wire:loading.remove wire:target="login">
                Ingresar al Sistema
            </span>
            <span wire:loading wire:target="login">
                <i class="ri-loader-4-line" style="display: inline-block; animation: spin 0.8s linear infinite; margin-right: 6px;"></i>Verificando...
            </span>
        </button>
    </form>
</div>
