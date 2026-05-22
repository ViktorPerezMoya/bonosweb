<div style="display: flex; flex-direction: column; gap: 2rem; margin: 0 auto;">
    <!-- Información del Perfil (Email) -->
    <div class="glass-panel" style="padding: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 500; color: var(--text-primary);">Información del Perfil</h3>
            <p style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">Actualiza la dirección de correo electrónico de tu cuenta.</p>
        </div>

        <form wire:submit="updateEmail" style="max-width: 500px;">
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input wire:model="email" id="email" type="email" class="form-control" required>
                @error('email') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    Guardar Email
                </button>

                @if (session('status') === 'email-updated')
                    <p style="font-size: 0.9rem; color: var(--success); margin: 0;">
                        Guardado exitosamente.
                    </p>
                @endif
            </div>
        </form>
    </div>

    <!-- Cambiar Contraseña -->
    <div class="glass-panel" style="padding: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 500; color: var(--text-primary);">Actualizar Contraseña</h3>
            <p style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--text-secondary);">Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerte seguro.</p>
        </div>

        <form wire:submit="updatePassword" style="max-width: 500px;">
            <div class="form-group">
                <label for="current_password" class="form-label">Contraseña Actual</label>
                <input wire:model="current_password" id="current_password" type="password" class="form-control" required>
                @error('current_password') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Nueva Contraseña</label>
                <input wire:model="password" id="password" type="password" class="form-control" required autocomplete="new-password">
                @error('password') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                @error('password_confirmation') <span style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem; display: block;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    Actualizar Contraseña
                </button>

                @if (session('status') === 'password-updated')
                    <p style="font-size: 0.9rem; color: var(--success); margin: 0;">
                        Contraseña actualizada.
                    </p>
                @endif
            </div>
        </form>
    </div>
</div>
