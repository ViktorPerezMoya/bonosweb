<div>

    {{-- ══ PANEL: Uploads ═════════════════════════════════════════════════════ --}}
    <div class="glass-panel" style="margin-bottom: 1.5rem;">
        <h3 style="margin-bottom: 0.3rem; font-size: 1rem;">Archivos de Configuración</h3>
        <p style="color: var(--text-secondary); font-size: 0.83rem; margin-bottom: 1.5rem;">
            Subí un bono de muestra para ver el contexto y la imagen PNG/JPG de la firma del empleador.
        </p>

        @if($uploadError)
            <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--danger); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.85rem; display: flex; gap: 0.5rem; align-items: flex-start;">
                <i class="ri-error-warning-line" style="flex-shrink: 0; margin-top: 0.1rem;"></i>
                {{ $uploadError }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

            {{-- PDF de muestra --}}
            <div style="border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem;">
                    <i class="ri-file-pdf-line" style="font-size: 1.3rem; color: var(--accent);"></i>
                    <strong style="font-size: 0.9rem;">PDF de Muestra</strong>
                    @if($previewAvailable)
                        <span style="margin-left: auto; font-size: 0.75rem; color: var(--success);">
                            <i class="ri-checkbox-circle-fill"></i> Listo
                        </span>
                    @endif
                </div>
                <form wire:submit.prevent="uploadSamplePdf" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="file" wire:model="samplePdf" accept=".pdf"
                        style="flex: 1; font-size: 0.8rem; color: var(--text-secondary); background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 0.35rem 0.5rem;">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto"
                        style="font-size: 0.8rem; padding: 0.4rem 0.9rem;"
                        wire:loading.attr="disabled" wire:target="uploadSamplePdf">
                        <span wire:loading.remove wire:target="uploadSamplePdf">Subir</span>
                        <span wire:loading wire:target="uploadSamplePdf">
                            <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i>
                        </span>
                    </button>
                </form>
                @error('samplePdf') <p style="color: var(--danger); font-size: 0.78rem; margin-top: 0.4rem;">{{ $message }}</p> @enderror
                <p style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.6rem;">
                    El PDF se previsualiza en el canvas. Max 20 MB.
                </p>

            </div>

            {{-- Imagen de firma --}}
            <div style="border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem;">
                    <i class="ri-quill-pen-line" style="font-size: 1.3rem; color: var(--accent);"></i>
                    <strong style="font-size: 0.9rem;">Imagen de Firma</strong>
                    @if($signatureAvailable)
                        <span style="margin-left: auto; font-size: 0.75rem; color: var(--success);">
                            <i class="ri-checkbox-circle-fill"></i> Cargada
                        </span>
                    @endif
                </div>
                <form wire:submit.prevent="uploadSignatureImage" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="file" wire:model="signatureImage" accept=".png,.jpg,.jpeg"
                        style="flex: 1; font-size: 0.8rem; color: var(--text-secondary); background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 0.35rem 0.5rem;">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto"
                        style="font-size: 0.8rem; padding: 0.4rem 0.9rem;"
                        wire:loading.attr="disabled" wire:target="uploadSignatureImage">
                        <span wire:loading.remove wire:target="uploadSignatureImage">Subir</span>
                        <span wire:loading wire:target="uploadSignatureImage">
                            <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i>
                        </span>
                    </button>
                </form>
                @error('signatureImage') <p style="color: var(--danger); font-size: 0.78rem; margin-top: 0.4rem;">{{ $message }}</p> @enderror
                <p style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.6rem;">
                    PNG con fondo transparente recomendado. Max 5 MB.
                </p>
            </div>

        </div>
    </div>

    {{-- ══ PANEL: Ubicación Multi-página ══════════════════════════════════════ --}}
    <div class="glass-panel" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem;">
            <i class="ri-pages-line" style="font-size: 1.2rem; color: var(--accent);"></i>
            <h3 style="margin: 0; font-size: 1rem;">Ubicación de la Firma en Documentos Multi-página</h3>
        </div>
        <p style="color: var(--text-secondary); font-size: 0.83rem; margin-bottom: 1.25rem;">
            Si el recibo de un empleado tiene más de una hoja, elige dónde debe estamparse la firma digital visual.
        </p>

        <div style="max-width: 400px;">
            <select wire:model.live="signature_page_placement" class="bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm text-white focus:ring-blue-500 focus:border-blue-500 w-full">
                <option value="all">En todas las hojas</option>
                <option value="first">Solo en la primera hoja</option>
                <option value="last">Solo en la última hoja</option>
            </select>
            <span wire:loading wire:target="signature_page_placement" class="text-xs text-blue-400 mt-1 block">
                <i class="ri-loader-4-line animate-spin mr-1"></i> Guardando...
            </span>
            @error('signature_page_placement') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- ══ PANEL: Canvas interactivo ══════════════════════════════════════════ --}}
    <div class="glass-panel" style="margin-bottom: 1.5rem;"
        x-data="sigCanvas(@js([
            'xMm'        => $sigXmm,
            'yMm'        => $sigYmm,
            'wMm'        => $sigWmm,
            'hMm'        => $sigHmm,
            'pageW'      => $pageWmm,
            'pageH'      => $pageHmm,
            'previewUrl' => $previewAvailable ? route('signature.preview') . '?v=' . $previewVersion : null,
            'sigUrl'     => $signatureAvailable ? route('signature.image') . '?v=' . time() : null,
        ]))"
        @mousemove.window="onMove($event)"
        @mouseup.window="stopInteraction()"
        @touchmove.window.prevent="onMove($event)"
        @touchend.window="stopInteraction()">

        {{-- Header del panel --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                    <h3 style="margin: 0; font-size: 1rem;">Posición de la Firma</h3>
                    @if($previewAvailable)
                        <div style="display: flex; align-items: center; margin-left: 1rem; gap: 0.5rem;">
                            <select wire:model.live="pdf_rotation" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs text-white focus:ring-blue-500 focus:border-blue-500">
                                <option value="0">Rotación: Original</option>
                                <option value="90">Rotar 90°</option>
                                <option value="270">Rotar 270°</option>
                            </select>
                            <span wire:loading wire:target="pdf_rotation" class="text-xs text-blue-400">
                                <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i> Girando...
                            </span>
                        </div>
                    @endif
                </div>
                <p style="color: var(--text-secondary); font-size: 0.8rem; margin: 0.2rem 0 0;">
                    Arrastrá el recuadro rojo. Tirá del triángulo inferior-derecho para redimensionar.
                </p>
            </div>

            {{-- Indicadores de coordenadas --}}
            <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.8rem; color: var(--text-secondary);">
                <span>X: <strong style="color: var(--text-primary);" x-text="xMmDisplay + ' mm'">—</strong></span>
                <span>Y: <strong style="color: var(--text-primary);" x-text="yMmDisplay + ' mm'">—</strong></span>
                <span>W: <strong style="color: var(--text-primary);" x-text="wMmDisplay + ' mm'">—</strong></span>
                <span>H: <strong style="color: var(--text-primary);" x-text="hMmDisplay + ' mm'">—</strong></span>
            </div>
        </div>

        {{-- Badge de estado --}}
        <div style="margin-bottom: 0.75rem; min-height: 1.5rem;">
            <span x-show="saved && !dirty"
                style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; color: var(--success); background: rgba(16,185,129,0.1); border: 1px solid var(--success); padding: 0.25rem 0.6rem; border-radius: 20px;">
                <i class="ri-checkbox-circle-line"></i> Posición guardada
            </span>
            <span x-show="dirty"
                style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; color: #f59e0b; background: rgba(245,158,11,0.1); border: 1px solid #f59e0b; padding: 0.25rem 0.6rem; border-radius: 20px;">
                <i class="ri-loader-4-line" style="animation: spin 0.6s linear infinite; display: inline-block;"></i> Guardando...
            </span>
            @if($sigConfigured && !$previewAvailable)
                <span style="margin-left: 0.5rem; font-size: 0.78rem; color: var(--text-secondary);">
                    <i class="ri-information-line"></i> Subí un PDF de muestra para ver el contexto visual.
                </span>
            @endif
        </div>

        {{-- ── Canvas A4 ─────────────────────────────────────────────────────── --}}
        {{-- Wrapper con aspect ratio A4 (210:297) --}}
        <div style="position: relative; width: 100%; max-width: 560px;">
            <div x-ref="canvas"
                style="position: relative; width: 100%; overflow: hidden; background: white; box-shadow: 0 4px 24px rgba(0,0,0,0.4); border-radius: 3px; cursor: crosshair; touch-action: none;">

                {{-- Preview: PDF renderizado en canvas por PDF.js --}}
                @if($previewAvailable)
                    <canvas x-ref="pdfCanvas" style="display: block; width: 100%; pointer-events: none; user-select: none;"></canvas>
                @else
                    {{-- Fallback: hoja en blanco con aspect-ratio real de la página --}}
                    <div :style="`padding-bottom: ${(pageH/pageW)*100}%`"></div>
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 0.5rem; color: #ccc;">
                        <i class="ri-file-line" style="font-size: 3rem; opacity: 0.3;"></i>
                        <span style="font-size: 0.75rem; opacity: 0.5;">Sin previsualización</span>
                    </div>
                @endif

                {{-- ── Recuadro de firma (draggable + resizable) ──────────────── --}}
                <div
                    @mousedown="startDrag($event)"
                    @touchstart.prevent="startDrag($event)"
                    :style="`
                        position: absolute;
                        left: ${sigX}px;
                        top: ${sigY}px;
                        width: ${sigW}px;
                        height: ${sigH}px;
                        border: 2px dashed #ef4444;
                        box-sizing: border-box;
                        cursor: grab;
                        user-select: none;
                        background: rgba(239,68,68,0.06);
                        border-radius: 2px;
                    `">

                    {{-- Imagen de firma dentro del recuadro --}}
                    @if($signatureAvailable)
                        <img src="{{ route('signature.image') }}?v={{ time() }}"
                            style="width: 100%; height: 100%; object-fit: contain; pointer-events: none; user-select: none; display: block;">
                    @else
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #ef4444; opacity: 0.7; pointer-events: none;">
                            [Firma del Empleador]
                        </div>
                    @endif

                    {{-- Handle de resize (esquina inferior-derecha) --}}
                    <div
                        @mousedown.stop="startResize($event)"
                        @touchstart.stop.prevent="startResize($event)"
                        style="position: absolute; bottom: -1px; right: -1px; width: 0; height: 0;
                               border-left: 12px solid transparent;
                               border-bottom: 12px solid #ef4444;
                               cursor: se-resize;">
                    </div>
                </div>

            </div>{{-- /canvas --}}
        </div>{{-- /wrapper --}}

    </div>{{-- /glass-panel canvas --}}

    {{-- ══ PANEL: Texto ancla ══════════════════════════════════════════════════ --}}
    <div class="glass-panel" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem;">
            <i class="ri-anchor-line" style="font-size: 1.2rem; color: var(--accent);"></i>
            <h3 style="margin: 0; font-size: 1rem;">Texto Ancla de Posicionamiento</h3>
        </div>
        <p style="color: var(--text-secondary); font-size: 0.83rem; margin-bottom: 1.25rem;">
            Si se configura, el sistema buscará esta cadena de texto en cada recibo y posicionará
            la firma justo arriba del lugar donde aparece, ignorando las coordenadas del canvas.
            Dejá vacío para usar únicamente la posición arrastrada.
        </p>

        <form wire:submit.prevent="saveAnchorText" style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 600px;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="anchorInput" style="display: block; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.35rem;">
                        Texto a buscar en el PDF
                    </label>
                    <input
                        id="anchorInput"
                        type="text"
                        wire:model="anchorText"
                        placeholder="Ej: Firma del Empleador"
                        maxlength="255"
                        style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 0.5rem 0.75rem; color: var(--text-primary); font-size: 0.88rem; outline: none;">
                    @error('anchorText')
                        <p style="color: var(--danger); font-size: 0.78rem; margin-top: 0.3rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="offsetYInput" style="display: block; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.35rem;">
                        Margen de separación vertical (mm)
                    </label>
                    <input
                        id="offsetYInput"
                        type="number"
                        step="0.1"
                        wire:model="anchorOffsetY"
                        style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 0.5rem 0.75rem; color: var(--text-primary); font-size: 0.88rem; outline: none;">
                    @error('anchorOffsetY')
                        <p style="color: var(--danger); font-size: 0.78rem; margin-top: 0.3rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary"
                        style="font-size: 0.85rem; padding: 0.45rem 1.1rem;"
                        wire:loading.attr="disabled" wire:target="saveAnchorText">
                    <span wire:loading.remove wire:target="saveAnchorText">
                        <i class="ri-save-line"></i> Guardar
                    </span>
                    <span wire:loading wire:target="saveAnchorText">
                        <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display: inline-block;"></i>
                    </span>
                </button>

                <button type="button" wire:click="generatePreview" wire:loading.attr="disabled"
                        class="btn bg-slate-700 hover:bg-slate-600 text-white"
                        style="font-size: 0.85rem; padding: 0.45rem 1.1rem; border: 1px solid var(--glass-border);">
                    <span wire:loading.remove wire:target="generatePreview">
                        <i class="ri-eye-line mr-1"></i> Previsualizar Ajuste
                    </span>
                    <span wire:loading wire:target="generatePreview">
                        <i class="ri-loader-4-line animate-spin mr-1" style="display: inline-block;"></i> Procesando...
                    </span>
                </button>

                @if($anchorText)
                    <span style="font-size: 0.78rem; color: var(--text-secondary);">
                        <i class="ri-anchor-line" style="color: var(--accent);"></i>
                        Activo: <em>{{ $anchorText }}</em>
                    </span>
                @else
                    <span style="font-size: 0.78rem; color: var(--text-secondary);">
                        <i class="ri-map-pin-line"></i> Usando posición del canvas (sin texto ancla).
                    </span>
                @endif
            </div>
        </form>
    </div>{{-- /glass-panel anchor --}}

    {{-- ══ Alpine.js: componente sigCanvas ══════════════════════════════════════ --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sigCanvas', (cfg) => ({
            // ── Dimensiones de referencia de la página (mm) ───────────────────
            pageW: cfg.pageW ?? 210,
            pageH: cfg.pageH ?? 297,

            // ── Posición/tamaño del recuadro en px de pantalla ───────────────
            sigX: 0, sigY: 0, sigW: 0, sigH: 0,

            // ── Dimensiones del contenedor ───────────────────────────────────
            cW: 0, cH: 0,

            // ── Estado de arrastre ───────────────────────────────────────────
            dragging: false,
            dX: 0, dY: 0,    // posición del mouse al iniciar drag
            bX: 0, bY: 0,    // posición del recuadro al iniciar drag

            // ── Estado de resize ─────────────────────────────────────────────
            resizing: false,
            rX: 0, rY: 0,    // posición del mouse al iniciar resize
            rW: 0, rH: 0,    // dimensiones del recuadro al iniciar resize

            // ── UI ───────────────────────────────────────────────────────────
            dirty: false,
            saved: false,

            // ── Inicialización ───────────────────────────────────────────────
            async init() {
                this.$nextTick(() => this.onResize());
                window.addEventListener('resize', () => this.onResize());

                if (cfg.previewUrl) {
                    await this.$nextTick();
                    this.renderPdf(cfg.previewUrl);
                }

                // Re-renderizar cuando se sube un nuevo PDF
                window.addEventListener('preview-ready', (e) => {
                    // Actualizar dimensiones de página detectadas por FPDI
                    if (e.detail && e.detail.pageW) {
                        this.pageW = e.detail.pageW;
                        this.pageH = e.detail.pageH;
                    }
                    this.$nextTick(() =>
                        this.renderPdf('/configuracion/firma/preview-image?v=' + Date.now())
                    );
                });
            },

            // ── Renderizar página 1 del PDF vía PDF.js ────────────────────────
            async renderPdf(url) {
                if (!window.pdfjsLib) return;
                try {
                    pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                    const pdf  = await pdfjsLib.getDocument({ url, withCredentials: true }).promise;
                    const page = await pdf.getPage(1);

                    const pdfCanvas = this.$refs.pdfCanvas;
                    if (!pdfCanvas) return;

                    const containerWidth = this.$refs.canvas.offsetWidth;
                    const viewport       = page.getViewport({ scale: 1 });
                    const scale          = containerWidth / viewport.width;
                    const scaledViewport = page.getViewport({ scale });

                    pdfCanvas.width  = scaledViewport.width;
                    pdfCanvas.height = scaledViewport.height;

                    await page.render({
                        canvasContext: pdfCanvas.getContext('2d'),
                        viewport: scaledViewport,
                    }).promise;

                    this.onResize();
                } catch (e) {
                    console.warn('Error renderizando PDF preview:', e);
                }
            },

            onResize() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;

                const w = canvas.offsetWidth;
                if (w === 0) return; // contenedor no visible aún

                // Siempre proporciones reales de la página para el sistema de coordenadas.
                // NO leer canvas.offsetHeight: el <canvas> de PDF.js tiene 150px
                // por defecto antes de renderizar, lo que corrompe el cálculo px↔mm.
                const h = Math.round(w * (this.pageH / this.pageW));

                // Sin previsualización PDF hay que imponer la altura manualmente
                if (!cfg.previewUrl) {
                    canvas.style.height = h + 'px';
                }

                const prevCW = this.cW;
                this.cW = w;
                this.cH = h;

                if (prevCW === 0) {
                    // Primera vez: convertir mm → px.
                    // sigXmm/sigYmm siempre son números (0.0 = nuevo tenant → esquina sup-izq).
                    this.sigX = Math.max(0, (cfg.xMm / this.pageW) * this.cW);
                    this.sigY = Math.max(0, (cfg.yMm / this.pageH) * this.cH);
                    this.sigW = Math.max(10, (cfg.wMm / this.pageW) * this.cW);
                    this.sigH = Math.max(8,  (cfg.hMm / this.pageH) * this.cH);
                } else if (prevCW !== w) {
                    // Resize de ventana: reescalar posición proporcionalmente
                    const ratio = w / prevCW;
                    this.sigX *= ratio; this.sigY *= ratio;
                    this.sigW *= ratio; this.sigH *= ratio;
                }
            },

            // ── Drag ─────────────────────────────────────────────────────────
            startDrag(e) {
                this.dragging = true;
                this.dirty = true; this.saved = false;
                const p = e.touches ? e.touches[0] : e;
                this.dX = p.clientX; this.dY = p.clientY;
                this.bX = this.sigX;  this.bY = this.sigY;
                e.preventDefault();
            },

            // ── Resize ───────────────────────────────────────────────────────
            startResize(e) {
                this.resizing = true;
                this.dirty = true; this.saved = false;
                const p = e.touches ? e.touches[0] : e;
                this.rX = p.clientX; this.rY = p.clientY;
                this.rW = this.sigW;  this.rH = this.sigH;
                e.preventDefault();
            },

            // ── Movimiento del mouse/touch ────────────────────────────────────
            onMove(e) {
                if (!this.dragging && !this.resizing) return;
                const p = e.touches ? e.touches[0] : e;

                if (this.dragging) {
                    const dx = p.clientX - this.dX;
                    const dy = p.clientY - this.dY;
                    this.sigX = Math.max(0, Math.min(this.bX + dx, this.cW - this.sigW));
                    this.sigY = Math.max(0, Math.min(this.bY + dy, this.cH - this.sigH));
                }
                if (this.resizing) {
                    const dx = p.clientX - this.rX;
                    const dy = p.clientY - this.rY;
                    this.sigW = Math.max(20, Math.min(this.rW + dx, this.cW - this.sigX));
                    this.sigH = Math.max(10, Math.min(this.rH + dy, this.cH - this.sigY));
                }
            },

            // ── Soltar / fin de interacción → guardar ─────────────────────────
            stopInteraction() {
                if (!this.dragging && !this.resizing) return;
                this.dragging = false;
                this.resizing = false;
                // Enviar coordenadas al componente Livewire
                this.$wire.saveCoordinates(
                    Math.round(this.sigX), Math.round(this.sigY),
                    Math.round(this.sigW), Math.round(this.sigH),
                    Math.round(this.cW),   Math.round(this.cH)
                );
                this.saved = true;
                this.dirty = false;
            },

            // ── Getters: mostrar mm en la UI ──────────────────────────────────
            get xMmDisplay() { return this.cW ? ((this.sigX / this.cW) * this.pageW).toFixed(1) : '—'; },
            get yMmDisplay() { return this.cH ? ((this.sigY / this.cH) * this.pageH).toFixed(1) : '—'; },
            get wMmDisplay() { return this.cW ? ((this.sigW / this.cW) * this.pageW).toFixed(1) : '—'; },
            get hMmDisplay() { return this.cH ? ((this.sigH / this.cH) * this.pageH).toFixed(1) : '—'; },
        }));
    });
    </script>

    {{-- Modal de Previsualización (AlpineJS) --}}
    <div x-data="{ showPreview: false, pdfData: '' }"
         x-on:preview-generated.window="showPreview = true; pdfData = $event.detail.data"
         x-show="showPreview"
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        
        <div @click.away="showPreview = false" style="background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: var(--radius-md); width: 100%; max-width: 900px; height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;"><i class="ri-eye-line" style="color: var(--accent); margin-right: 0.5rem;"></i> Previsualización de Firma con Ancla</h3>
                <button @click="showPreview = false" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.25rem;">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            
            <div style="flex: 1; background: #525659; overflow: hidden;">
                <iframe :src="pdfData" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>

</div>
