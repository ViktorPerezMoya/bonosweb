<div style="min-height: 100vh; padding: 1.5rem 1rem; max-width: 600px; margin: 0 auto;" x-data="{ showSignModal: false, signType: '' }" @signature-success.window="showSignModal = false">
    {{-- ── Cabecera ──────────────────────────────────────────────────────────── --}}
    <div class="glass-panel" style="padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
        <div style="background: var(--accent); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="ri-user-line" style="font-size: 1.4rem; color: #fff;"></i>
        </div>
        <div>
            <div style="font-weight: 600; font-size: 1rem;">{{ Auth::user()->name }}</div>
            <div style="font-size: 0.82rem; color: var(--text-secondary);">Mis Recibos de Sueldo</div>
        </div>
        <div style="margin-left: auto;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        style="background: transparent; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; color: var(--text-secondary); font-size: 0.8rem; cursor: pointer;">
                    <i class="ri-logout-box-r-line"></i> Salir
                </button>
            </form>
        </div>
    </div>

    {{-- ── Lista de recibos ──────────────────────────────────────────────────── --}}
    @if ($payslips->isEmpty())
        <div class="glass-panel" style="padding: 3rem 1.5rem; text-align: center;">
            <i class="ri-file-list-3-line" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 1rem;"></i>
            <p style="color: var(--text-secondary);">Todavía no tenés recibos disponibles.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach ($payslips as $payslip)
                @php
                    $meses = [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ];
                    $tipoLabels = [
                        'mensual'      => 'Mensual',
                        'sac'          => 'SAC',
                        'vacaciones'   => 'Vacaciones',
                        'gratificacion'=> 'Gratificación',
                        'final'        => 'Liquidación Final',
                        'retroactivo'  => 'Retroactivo',
                        'quincena'     => 'Quincena',
                        'anticipo'     => 'Anticipo',
                    ];
                    $mes       = $meses[$payslip->period_month] ?? $payslip->period_month;
                    $tipo      = $tipoLabels[$payslip->liquidation_type] ?? $payslip->liquidation_type;
                    $isSigned  = $payslip->status === 'signed_conforme' || $payslip->status === 'signed_no_conforme' || $payslip->status === 'signed';
                @endphp

                <div class="glass-panel"
                     style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem;">

                    {{-- Icono según estado --}}
                    <div style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%;
                                background: {{ $isSigned ? 'rgba(16,185,129,0.15)' : 'rgba(251,191,36,0.15)' }};
                                display: flex; align-items: center; justify-content: center;">
                        <i class="{{ $isSigned ? 'ri-shield-check-line' : 'ri-time-line' }}"
                           style="font-size: 1.25rem; color: {{ $isSigned ? 'var(--success)' : 'var(--warning)' }};"></i>
                    </div>

                    {{-- Datos del recibo --}}
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $mes }} {{ $payslip->period_year }}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">
                            {{ $tipo }}
                            &nbsp;·&nbsp;
                            <span style="color: {{ $isSigned ? 'var(--success)' : 'var(--warning)' }}; font-weight: 500;">
                                {{ $isSigned ? 'Firmado' : 'Pendiente' }}
                            </span>
                        </div>
                    </div>

                    {{-- Botón ver --}}
                    <button wire:click="openViewer({{ $payslip->id }})"
                       style="flex-shrink: 0; display: flex; align-items: center; justify-content: center;
                              width: 38px; height: 38px; border-radius: var(--radius-sm);
                              background: var(--surface-2); color: var(--accent); border: none; cursor: pointer;
                              transition: background 0.2s;"
                       onmouseover="this.style.background='var(--accent)';this.style.color='#fff'"
                       onmouseout="this.style.background='var(--surface-2)';this.style.color='var(--accent)'"
                       title="Ver recibo">
                        <i class="ri-eye-line" style="font-size: 1.1rem;"></i>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        @if ($payslips->hasPages())
            <div style="margin-top: 1.5rem;">
                {{ $payslips->links() }}
            </div>
        @endif
    @endif

    {{-- ── Modal del Visor Seguro ────────────────────────────────────────────── --}}
    @if($showModal && $this->selectedPayslip)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/80 backdrop-blur-sm p-0 md:p-4">
            <div class="rounded-none md:rounded-xl shadow-2xl w-full h-full md:max-w-5xl md:h-[90vh] flex flex-col overflow-hidden relative" style="background: var(--bg-primary); border: 1px solid var(--glass-border);">
                
                {{-- Toolbar del Modal --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-4 shrink-0" style="background: var(--bg-secondary); border-bottom: 1px solid var(--glass-border);">
                    <div class="font-semibold flex items-center gap-3" style="color: var(--text-primary);">
                        <h2>Visor de Recibo</h2>
                        @if($this->selectedPayslip->status === 'signed_conforme')
                            <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded border border-emerald-500/30 font-medium flex items-center">
                                <i class="ri-shield-check-fill mr-1"></i> Firmado Conforme
                            </span>
                        @elseif($this->selectedPayslip->status === 'signed_no_conforme')
                            <span class="px-2 py-1 bg-orange-500/20 text-orange-400 text-xs rounded border border-orange-500/30 font-medium flex items-center">
                                <i class="ri-shield-check-fill mr-1"></i> Firmado No Conforme
                            </span>
                        @endif
                        <button wire:click="closeViewer" class="md:hidden p-1 ml-auto" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                        @if($this->selectedPayslip->status === 'pending')
                            <button @click="showSignModal = true; signType = 'conforme'; $wire.setSignatureType('conforme')" class="w-full sm:w-auto btn px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition text-sm">
                                <i class="ri-check-line mr-1"></i> Firmar Conforme
                            </button>
                            <button @click="showSignModal = true; signType = 'no_conforme'; $wire.setSignatureType('no_conforme')" class="w-full sm:w-auto btn px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded transition text-sm">
                                <i class="ri-close-line mr-1"></i> Firmar No Conforme
                            </button>
                        @else
                            <a href="{{ route('payslips.view', $this->selectedPayslip->id) }}" target="_blank" class="w-full sm:w-auto text-center btn px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition text-sm">
                                <i class="ri-download-line mr-1"></i> Descargar PDF
                            </a>
                        @endif
                        <button wire:click="closeViewer" class="hidden md:flex items-center justify-center p-2 ml-2 transition-colors" style="color: var(--text-secondary);" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-secondary)'">
                            <i class="ri-close-line text-3xl"></i>
                        </button>
                    </div>
                </div>

                {{-- Contenido Principal --}}
                <div class="flex-1 relative overflow-hidden" style="background: var(--bg-primary);">
                    
                    {{-- Visor PDF Seguro (PDF.js + AlpineJS) --}}
                    <div class="absolute inset-0 flex flex-col" 
                         oncontextmenu="return false;" 
                         wire:ignore
                         wire:key="visor-pdf-{{ $this->selectedPayslip->id }}-{{ $this->selectedPayslip->status }}"
                         x-data="pdfViewer('{{ route('payslips.view', $this->selectedPayslip->id) }}?v={{ $this->selectedPayslip->status }}')">
                        
                        {{-- Barra de Controles Custom --}}
                        <div class="flex justify-center items-center gap-4 py-2 shrink-0 z-10" style="background: var(--bg-secondary); border-bottom: 1px solid var(--glass-border);">
                            <button @click="zoomOut" class="px-2 py-1 rounded transition" style="color: var(--text-primary); background: var(--input-bg);"><i class="ri-zoom-out-line"></i></button>
                            <span class="text-sm font-medium w-12 text-center" x-text="Math.round(scale * 100) + '%'" style="color: var(--text-primary);"></span>
                            <button @click="zoomIn" class="px-2 py-1 rounded transition" style="color: var(--text-primary); background: var(--input-bg);"><i class="ri-zoom-in-line"></i></button>
                            <button @click="resetZoom" class="px-3 py-1 rounded text-sm ml-2 transition" style="color: var(--text-primary); background: var(--input-bg);"><i class="ri-refresh-line mr-1"></i>Reset</button>
                        </div>

                        {{-- Contenedor del Canvas --}}
                        <div class="flex-1 min-h-0 overflow-auto p-4 text-center" style="overflow: auto;">
                            <canvas x-ref="pdfCanvas" @contextmenu.prevent class="shadow-2xl inline-block max-w-none"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Modal Interno Flotante para Firma --}}
                <div x-show="showSignModal" x-cloak class="absolute inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div class="shadow-2xl rounded-xl w-full max-w-sm p-6" style="background: var(--bg-secondary); border: 1px solid var(--glass-border);">
                        <h3 class="font-medium text-lg mb-4" x-text="signType === 'conforme' ? 'Firma en Conformidad' : 'Firma en Disconformidad'" style="color: var(--text-primary);"></h3>
                        
                        <div x-show="signType === 'no_conforme'" class="mb-4">
                            <label class="block text-sm mb-1" style="color: var(--text-secondary);">Motivo de Disconformidad</label>
                            <select wire:model="disagreementReasonId" class="w-full form-control">
                                <option value="">-- Seleccione un motivo --</option>
                                @foreach($this->activeReasons as $reason)
                                    <option value="{{ $reason->id }}">{{ $reason->reason_text }}</option>
                                @endforeach
                            </select>
                            @error('disagreementReasonId') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm mb-1" style="color: var(--text-secondary);">Contraseña de acceso</label>
                            <input type="password" wire:model="signaturePassword" placeholder="Tu contraseña" class="w-full form-control">
                            @error('signaturePassword') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="showSignModal = false" class="flex-1 py-2 rounded transition text-sm" style="color: var(--text-primary); background: var(--input-bg);" onmouseover="this.style.background='var(--nav-hover)'" onmouseout="this.style.background='var(--input-bg)'">
                                Cancelar
                            </button>
                            <button wire:click="signPayslip" wire:loading.attr="disabled" :class="signType === 'conforme' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'" class="flex-1 py-2 rounded text-white font-medium transition text-sm flex justify-center">
                                <span wire:loading.remove wire:target="signPayslip">Confirmar Firma</span>
                                <span wire:loading wire:target="signPayslip"><i class="ri-loader-4-line animate-spin"></i> Procesando...</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endif

    {{-- ── Scripts para PDF.js y AlpineJS ────────────────────────────────────── --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfViewer', (url) => {
                // Variable NO reactiva para evitar el error de Proxy con campos privados
                let rawPdfDoc = null;

                return {
                    pageNum: 1,
                    scale: 1.0,
                    canvas: null,
                    ctx: null,
                    isRendering: false,

                    init() {
                        this.canvas = this.$refs.pdfCanvas;
                        this.ctx = this.canvas.getContext('2d');
                        
                        // Configurar el Worker de PDF.js
                        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                        // Cargar el documento PDF en memoria
                        pdfjsLib.getDocument(url).promise.then(pdf => {
                            rawPdfDoc = pdf;
                            this.renderPage(this.pageNum);
                        }).catch(err => {
                            console.error("Error al cargar el PDF:", err);
                        });
                    },

                    renderPage(num) {
                        this.isRendering = true;
                        rawPdfDoc.getPage(num).then(page => {
                            const viewport = page.getViewport({ scale: this.scale });
                            
                            // Ajustar tamaño del canvas al viewport calculado
                            this.canvas.height = viewport.height;
                            this.canvas.width = viewport.width;

                            const renderContext = {
                                canvasContext: this.ctx,
                                viewport: viewport
                            };

                            page.render(renderContext).promise.then(() => {
                                this.isRendering = false;
                            });
                        });
                    },

                    zoomIn() {
                        if (this.scale >= 3) return;
                        this.scale += 0.25;
                        this.renderPage(this.pageNum);
                    },

                    zoomOut() {
                        if (this.scale <= 0.5) return;
                        this.scale -= 0.25;
                        this.renderPage(this.pageNum);
                    },

                    resetZoom() {
                        this.scale = 1.0;
                        this.renderPage(this.pageNum);
                    }
                }; // Fin del return
            }); // Fin del Alpine.data
        });
    </script>
</div>
