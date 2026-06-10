<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Columna Izquierda: Concepto y Descarga -->
        <div class="glass-panel" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 2rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <i class="ri-shield-keyhole-line" style="font-size: 2.5rem; color: var(--success);"></i>
            </div>
            
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Certificado Raíz de BonosWeb</h2>
            
            <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.6;">
                Para que los recibos de sueldo descargados de esta plataforma sean reconocidos como 100% válidos por tu computadora y por visores como Adobe Acrobat Reader, necesitas instalar nuestro Certificado Raíz. Esto establece una cadena de confianza garantizando que los documentos no han sido alterados y asegurando la validez legal de las firmas digitales.
            </p>

            <button wire:click="download" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-download-cloud-2-line"></i> Descargar Certificado (.crt)
            </button>
            
            @if (session()->has('error'))
                <div style="margin-top: 1rem; color: var(--danger); font-size: 0.9rem;">
                    <i class="ri-error-warning-line"></i> {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Columna Derecha: Tutorial de Instalación -->
        <div class="glass-panel">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-book-open-line" style="color: var(--primary);"></i> Guía de Instalación Rápida
            </h3>

            <div class="space-y-4">
                <!-- Paso 1 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">1</div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Descarga el archivo</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Haz clic en el botón de descarga y guarda el archivo <code>BonosWeb_Root_CA.crt</code> en tu PC.</p>
                    </div>
                </div>

                <!-- Paso 2 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">2</div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Abrir en Adobe Reader</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Abre cualquier recibo de sueldo firmado en Adobe Acrobat Reader.</p>
                    </div>
                </div>

                <!-- Paso 3 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">3</div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Panel de Firmas</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Haz clic en el panel de firmas (icono de bolígrafo a la izquierda). Si la firma dice "Desconocida", haz clic derecho sobre ella y selecciona "Mostrar propiedades de la firma".</p>
                    </div>
                </div>

                <!-- Paso 4 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">4</div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Mostrar Certificado</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Haz clic en "Mostrar certificado del firmante".</p>
                    </div>
                </div>

                <!-- Paso 5 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">5</div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Confiar</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Ve a la pestaña "Confianza" y haz clic en "Agregar a certificados de confianza".</p>
                    </div>
                </div>

                <!-- Paso 6 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="min-width: 30px; height: 30px; border-radius: 50%; background: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;"><i class="ri-check-line"></i></div>
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.25rem;">Confirmar</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">Marca las casillas para confiar en este certificado como raíz de confianza y acepta. A partir de ahora, todos los recibos de BonosWeb aparecerán con un check verde de validez.</p>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary); border-radius: 0 8px 8px 0;">
                <p style="font-size: 0.85rem; color: var(--text-secondary);">
                    <strong>Nota:</strong> Solo necesitas realizar este procedimiento <strong>una vez</strong> en tu computadora. Todos los futuros documentos firmados por nuestra plataforma serán validados automáticamente.
                </p>
            </div>
        </div>

    </div>
</div>
