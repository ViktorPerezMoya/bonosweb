<div class="space-y-6">

    {{-- Título --}}
    <div>
        <p class="text-sm" style="color: var(--text-secondary); margin-top: 0.25rem;">
            Personaliza el logo y la imagen de fondo del portal de tu empresa.
        </p>
    </div>

    {{-- Grid: 1 col en mobile, 2 cols en md+ --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

        {{-- ── Panel Logo ──────────────────────────────────────────────────── --}}
        <div class="glass-panel p-5 space-y-4"
             x-data="{
                 logoPreview: null,
                 readFile(event) {
                     const file = event.target.files[0];
                     if (!file) { this.logoPreview = null; return; }
                     const reader = new FileReader();
                     reader.onload = e => { this.logoPreview = e.target.result; };
                     reader.readAsDataURL(file);
                 }
             }">

            <div class="flex items-center gap-2">
                <i class="ri-image-line text-accent text-lg"></i>
                <h3 class="text-base font-semibold text-white">Logo de la empresa</h3>
            </div>
            <p class="text-xs" style="color: var(--text-secondary);">
                Se mostrará en la barra de navegación. Formatos: PNG o SVG. Máximo 1 MB.
            </p>

            {{-- Previsualización actual --}}
            <div class="rounded-lg flex items-center justify-center overflow-hidden"
                 style="background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); min-height: 80px;">
                @if($currentLogoUrl)
                    <img src="{{ $currentLogoUrl }}" alt="Logo actual"
                         class="h-14 w-auto object-contain max-w-[200px] p-2">
                @else
                    <span class="text-xs py-4" style="color: var(--text-muted);">Sin logo configurado</span>
                @endif
            </div>

            {{-- Preview client-side (Alpine FileReader) --}}
            <div x-show="logoPreview"
                 class="rounded-lg flex flex-col items-center gap-2 py-3"
                 style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.3); display:none;">
                <img :src="logoPreview" alt="Vista previa"
                     class="h-14 w-auto object-contain max-w-[220px]">
                <span class="text-xs" style="color: var(--text-secondary);">Nueva imagen seleccionada</span>
            </div>

            {{-- Indicador de carga Livewire --}}
            <div wire:loading wire:target="logo"
                 class="text-xs text-center py-2 rounded-lg"
                 style="background: rgba(59,130,246,0.1); color: var(--accent); border: 1px solid rgba(59,130,246,0.2);">
                <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display:inline-block; margin-right:4px;"></i>
                Procesando...
            </div>

            @if(session('logo_saved'))
                <div class="text-sm rounded-lg px-3 py-2 text-center"
                     style="background: rgba(16,185,129,0.1); color: var(--success); border: 1px solid var(--success);">
                    <i class="ri-check-line mr-1"></i>{{ session('logo_saved') }}
                </div>
            @endif

            @error('logo')
                <p class="text-xs" style="color: var(--danger);">{{ $message }}</p>
            @enderror

            {{-- Zona de subida + acciones --}}
            <div class="flex flex-col gap-2">
                <label class="flex flex-col items-center justify-center gap-2 rounded-lg cursor-pointer py-4 px-3 transition-colors hover:bg-white/5"
                       style="border: 1px dashed rgba(255,255,255,0.2);">
                    <i class="ri-upload-cloud-2-line text-2xl" style="color: var(--text-muted);"></i>
                    <span class="text-xs text-center" style="color: var(--text-secondary);">
                        Seleccionar PNG o SVG
                    </span>
                    <input type="file" wire:model="logo" accept=".png,.svg" class="sr-only"
                           @change="readFile($event)">
                </label>

                <div class="flex gap-2">
                    <button wire:click="saveLogo"
                            wire:loading.attr="disabled"
                            wire:target="saveLogo,logo"
                            class="btn btn-primary flex-1"
                            :disabled="!logoPreview"
                            :style="!logoPreview ? 'opacity:0.5; pointer-events:none;' : ''">
                        <span wire:loading.remove wire:target="saveLogo">
                            <i class="ri-save-line" style="margin-right:4px;"></i> Guardar logo
                        </span>
                        <span wire:loading wire:target="saveLogo">
                            <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display:inline-block; margin-right:4px;"></i> Guardando...
                        </span>
                    </button>

                    @if($currentLogoUrl)
                        <button wire:click="removeLogo"
                                wire:confirm="¿Eliminar el logo actual?"
                                class="btn"
                                style="background: rgba(239,68,68,0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); padding: 0.5rem 0.75rem;">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    @endif
                </div>
            </div>

            <div class="flex items-start gap-2 mt-3 pt-3 border-t border-white/10">
                <div class="flex items-center h-5">
                    <input wire:model.live="hideNameInMenu" id="hideNameInMenu" type="checkbox" class="w-4 h-4 text-accent bg-transparent border-gray-600 rounded focus:ring-accent focus:ring-2">
                </div>
                <label for="hideNameInMenu" class="text-xs text-gray-300">
                    Ocultar nombre de la empresa en el menú <br>
                    <span class="text-[10px] text-gray-500">(Recomendado para logos anchos o tipográficos)</span>
                </label>
            </div>

            <p class="text-xs" style="color: var(--text-muted);">
                <i class="ri-information-line mr-1"></i>
                Recomendado: fondo transparente, ratio aproximado 3:1.
            </p>
        </div>

        {{-- ── Panel Fondo de Login ─────────────────────────────────────────── --}}
        <div class="glass-panel p-5 space-y-4"
             x-data="{
                 bgPreview: null,
                 readFile(event) {
                     const file = event.target.files[0];
                     if (!file) { this.bgPreview = null; return; }
                     const reader = new FileReader();
                     reader.onload = e => { this.bgPreview = e.target.result; };
                     reader.readAsDataURL(file);
                 }
             }">

            <div class="flex items-center gap-2">
                <i class="ri-landscape-line text-accent text-lg"></i>
                <h3 class="text-base font-semibold text-white">Fondo pantalla de login</h3>
            </div>
            <p class="text-xs" style="color: var(--text-secondary);">
                Se mostrará como fondo al ingresar al sistema. Formatos: JPEG, JPG o WebP. Máximo 3 MB.
            </p>

            {{-- Previsualización actual --}}
            <div class="rounded-lg overflow-hidden relative"
                 style="aspect-ratio: 16/9; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15);">
                @if($currentBgUrl)
                    <img src="{{ $currentBgUrl }}" alt="Fondo actual"
                         class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="rounded-lg px-3 py-1 text-xs font-medium"
                             style="background: rgba(0,0,0,0.6); color: #fff; backdrop-filter: blur(4px);">
                            Fondo actual
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-center h-full">
                        <span class="text-xs" style="color: var(--text-muted);">Sin fondo configurado</span>
                    </div>
                @endif
            </div>

            {{-- Preview client-side (Alpine FileReader) --}}
            <div x-show="bgPreview"
                 class="rounded-lg overflow-hidden relative"
                 style="aspect-ratio: 16/9; border: 1px solid rgba(59,130,246,0.3); display:none;">
                <img :src="bgPreview" alt="Vista previa" class="w-full h-full object-cover">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="rounded-lg px-3 py-1 text-xs font-medium"
                         style="background: rgba(0,0,0,0.6); color: #fff; backdrop-filter: blur(4px);">
                        Nueva imagen seleccionada
                    </div>
                </div>
            </div>

            {{-- Indicador de carga Livewire --}}
            <div wire:loading wire:target="loginBackground"
                 class="text-xs text-center py-2 rounded-lg"
                 style="background: rgba(59,130,246,0.1); color: var(--accent); border: 1px solid rgba(59,130,246,0.2);">
                <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display:inline-block; margin-right:4px;"></i>
                Procesando...
            </div>

            @if(session('bg_saved'))
                <div class="text-sm rounded-lg px-3 py-2 text-center"
                     style="background: rgba(16,185,129,0.1); color: var(--success); border: 1px solid var(--success);">
                    <i class="ri-check-line mr-1"></i>{{ session('bg_saved') }}
                </div>
            @endif

            @error('loginBackground')
                <p class="text-xs" style="color: var(--danger);">{{ $message }}</p>
            @enderror

            {{-- Zona de subida + acciones --}}
            <div class="flex flex-col gap-2">
                <label class="flex flex-col items-center justify-center gap-2 rounded-lg cursor-pointer py-4 px-3 transition-colors hover:bg-white/5"
                       style="border: 1px dashed rgba(255,255,255,0.2);">
                    <i class="ri-upload-cloud-2-line text-2xl" style="color: var(--text-muted);"></i>
                    <span class="text-xs text-center" style="color: var(--text-secondary);">
                        Seleccionar JPEG, JPG o WebP
                    </span>
                    <input type="file" wire:model="loginBackground" accept=".jpg,.jpeg,.webp" class="sr-only"
                           @change="readFile($event)">
                </label>

                <div class="flex gap-2">
                    <button wire:click="saveBackground"
                            wire:loading.attr="disabled"
                            wire:target="saveBackground,loginBackground"
                            class="btn btn-primary flex-1"
                            :disabled="!bgPreview"
                            :style="!bgPreview ? 'opacity:0.5; pointer-events:none;' : ''">
                        <span wire:loading.remove wire:target="saveBackground">
                            <i class="ri-save-line" style="margin-right:4px;"></i> Guardar fondo
                        </span>
                        <span wire:loading wire:target="saveBackground">
                            <i class="ri-loader-4-line" style="animation: spin 0.8s linear infinite; display:inline-block; margin-right:4px;"></i> Guardando...
                        </span>
                    </button>

                    @if($currentBgUrl)
                        <button wire:click="removeBackground"
                                wire:confirm="¿Eliminar el fondo de login actual?"
                                class="btn"
                                style="background: rgba(239,68,68,0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); padding: 0.5rem 0.75rem;">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    @endif
                </div>
            </div>

            <p class="text-xs" style="color: var(--text-muted);">
                <i class="ri-information-line mr-1"></i>
                Recomendado: resolución mínima 1280×720 px para buena calidad.
            </p>
        </div>
    </div>

</div>
