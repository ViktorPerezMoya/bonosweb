<div>
    <div class="glass-panel" style="max-width: 600px; margin: 0 auto; transform: none;">
        <h3 style="margin-bottom: 1rem;">Carga Masiva (Archivo ZIP)</h3>
        <p style="margin-bottom: 2rem; font-size: 0.9rem;">
            Por favor, suba un único archivo .ZIP que contenga los recibos en formato PDF o .PDF(sabana).
            El sistema extraerá automáticamente cada PDF, leerá el CUIL del empleado y se lo asignará a su cuenta.
        </p>

        @if (session()->has('message'))
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="ri-checkbox-circle-line" style="font-size: 1.2rem;"></i>
                {{ session('message') }}
            </div>

            <a href="/dashboard" class="btn btn-primary" style="margin-bottom: 1rem;">Ir al Dashboard</a>
        @else
            <form wire:submit.prevent="save">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Año</label>
                        <input type="number" wire:model="period_year" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Mes</label>
                        <select wire:model="period_month" class="form-control" required>
                            @for($i=1; $i<=12; $i++)
                                <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Tipo de Liquidación</label>
                        <select wire:model="liquidation_type" class="form-control" required>
                            <option value="mensual">Mensual</option>
                            <option value="quincena">Quincena</option>
                            <option value="anticipo">Anticipo</option>
                            <option value="sac">SAC (Aguinaldo)</option>
                            <option value="vacaciones">Vacaciones</option>
                            <option value="gratificacion">Gratificación</option>
                            <option value="final">Liquidación Final</option>
                            <option value="retroactivo">Retroactivo</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Programar Notificación</label>
                        <input type="datetime-local" wire:model="notification_date" class="form-control" required>
                    </div>
                </div>

                <!-- Zona de Drag and Drop simulada con CSS -->
                <div class="upload-zone" style="border: 2px dashed var(--glass-border); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; position: relative; transition: all 0.3s; background: rgba(0,0,0,0.2); margin-bottom: 1.5rem;">

<input type="file" wire:model="zipFile" id="zipFile" accept=".zip,.pdf" x-on:livewire-upload-error="$wire.showUploadError()" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;">

                    <div wire:loading.remove wire:target="zipFile">
                        @if ($zipFile)
                            @php $ext = strtolower($zipFile->getClientOriginalExtension()); @endphp
                            <i class="ri-{{ $ext === 'pdf' ? 'file-pdf-fill' : 'file-zip-fill' }}" style="font-size: 4rem; color: var(--success); display: block; margin-bottom: 1rem;"></i>
                            <h4 style="color: var(--text-primary);">Archivo listo: {{ $zipFile->getClientOriginalName() }}</h4>
                            <p style="color: var(--success); font-size: 0.85rem; margin-top: 0.5rem;">Pulse "Procesar Archivo" para continuar.</p>
                        @else
                            <i class="ri-upload-cloud-2-line" style="font-size: 4rem; color: var(--accent); display: block; margin-bottom: 1rem;"></i>
                            <h4>Haz clic o arrastra un archivo aquí</h4>
                            <p style="font-size: 0.85rem; margin-top: 0.5rem;">Acepta <strong>.ZIP</strong> (bonos individuales) o <strong>.PDF</strong> (PDF masivo correlativo) · Máx. 50MB</p>
                        @endif
                    </div>

                    <div wire:loading wire:target="zipFile">
                        <i class="ri-loader-4-line" style="font-size: 4rem; color: var(--accent); display: block; margin-bottom: 1rem; animation: spin 1s linear infinite;"></i>
                        <h4>Cargando archivo...</h4>
                    </div>
                </div>

                @error('zipFile')
                    <div style="color: var(--danger); font-size: 0.85rem; margin-bottom: 1rem; text-align: center;">
                        <i class="ri-error-warning-line"></i> {{ $message }}
                    </div>
                @enderror

                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary" style="min-width: 200px;" {{ !$zipFile ? 'disabled' : '' }} wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="ri-settings-4-line" style="margin-right: 5px;"></i> Procesar Archivo
                        </span>
                        <span wire:loading wire:target="save">
                            <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i> Analizando y normalizando documento...
                        </span>
                    </button>
                </div>
            </form>
        @endif

        <!-- Modal de Error -->
        @if($showErrorModal)
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div class="glass-panel hover-disabled" style="max-width: 500px; width: 90%; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); transform: none;">
                    <i class="ri-error-warning-fill" style="font-size: 4rem; color: var(--danger); display: block; margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Error al Procesar</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem;">
                        {{ $errorMessage }}
                    </p>
                    <button type="button" class="btn" wire:click="closeErrorModal" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); width: 100%;">
                        Entendido, cerrar
                    </button>
                </div>
            </div>
        @endif

        <!-- Modal Warning: Firma No Configurada -->
        @if($showSignatureWarningModal)
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div class="glass-panel hover-disabled" style="max-width: 500px; width: 90%; text-align: center; border: 1px solid rgba(245, 158, 11, 0.3); transform: none;">
                    <i class="ri-error-warning-fill" style="font-size: 4rem; color: var(--warning); display: block; margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Firma No Configurada</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem;">
                        Para procesar recibos de sueldo, es obligatorio configurar previamente la firma digital y el sello de la empresa.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button type="button" class="btn" wire:click="closeSignatureWarningModal" style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--glass-border);">
                            Cancelar
                        </button>
                        <a href="/configuracion/firma" target="_blank" class="btn" style="background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid var(--warning); text-decoration: none;">
                            Ir a Configurar Firma
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal Confirmación: Previsualización de Firma -->
        @if($showSignaturePreviewModal)
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 1rem;">
                <div class="glass-panel hover-disabled" style="max-width: 90vw; width: 100%; height: 90vh; display: flex; flex-direction: column; overflow: hidden; padding: 0; transform: none;">
                    
                    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;"><i class="ri-eye-line" style="color: var(--accent); margin-right: 0.5rem;"></i> Confirmar Firma</h3>
                        <button wire:click="closeSignaturePreviewModal" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    <div style="flex: 1; background: #525659; overflow: hidden; position: relative;">
                        @if($signaturePreviewUrl)
                            <iframe src="{{ $signaturePreviewUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                        @else
                            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #ccc;">
                                <i class="ri-checkbox-circle-fill" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--text-primary);">Firma Configurada</h3>
                                <p style="color: var(--text-secondary); max-width: 400px; text-align: center; margin-top: 0.5rem;">La firma se aplicará de acuerdo a las coordenadas configuradas, pero no hay un recibo de muestra disponible para previsualizar.</p>
                            </div>
                        @endif
                    </div>

                    <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: flex-end; gap: 1rem; background: rgba(255,255,255,0.02);">
                        <a href="/configuracion/firma" target="_blank" class="btn" style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--glass-border); text-decoration: none;">
                            <i class="ri-settings-3-line" style="margin-right: 5px;"></i> Ajustar Firma
                        </a>
                        <button type="button" class="btn btn-primary" wire:click="proceedWithUpload" wire:loading.attr="disabled" wire:target="proceedWithUpload">
                            <span wire:loading.remove wire:target="proceedWithUpload">
                                <i class="ri-check-line" style="margin-right: 5px;"></i> Continuar y Procesar
                            </span>
                            <span wire:loading wire:target="proceedWithUpload">
                                <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i> Iniciando procesamiento...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        @endif
    </div>

    <style>
        .upload-zone:hover {
            border-color: var(--accent);
            background: rgba(59, 130, 246, 0.05) !important;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .hover-disabled:hover {
            transform: none !important;
        }
    </style>
</div>
