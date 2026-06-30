<div class="px-6 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white mb-2">Copias de Seguridad (GCS)</h1>
            <p class="text-white/60">Descarga un respaldo completo de todos los recibos de la empresa activa.</p>
        </div>
        <div>
            <button wire:click="calculateStats" class="btn btn-outline" wire:loading.attr="disabled">
                <i class="ri-refresh-line mr-2"></i> Recalcular
            </button>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="p-4 mb-6 bg-red-500/10 border border-red-500/50 rounded-lg text-red-500">
            <i class="ri-error-warning-line mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-surface/50 border border-white/10 rounded-xl p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-5 text-9xl transform translate-x-4 -translate-y-4">
                <i class="ri-folder-zip-line"></i>
            </div>
            <h3 class="text-white/60 text-sm font-medium uppercase tracking-wider mb-2">Archivos Respaldados</h3>
            <div class="text-4xl font-bold text-white mb-1">
                {{ number_format($fileCount) }}
            </div>
            <p class="text-white/40 text-sm">Recibos guardados de forma segura en Google Cloud</p>
        </div>

        <div class="bg-surface/50 border border-white/10 rounded-xl p-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-5 text-9xl transform translate-x-4 -translate-y-4">
                <i class="ri-hard-drive-2-line"></i>
            </div>
            <h3 class="text-white/60 text-sm font-medium uppercase tracking-wider mb-2">Tamaño Total</h3>
            <div class="text-4xl font-bold text-white mb-1">
                {{ number_format($totalSize / 1048576, 2) }} MB
            </div>
            <p class="text-white/40 text-sm">Espacio consumido en la nube por esta empresa</p>
        </div>
    </div>

    <div class="bg-surface border border-white/10 rounded-xl p-8 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-accent/20 text-accent text-4xl mb-6">
            <i class="ri-cloud-download-line"></i>
        </div>
        <h2 class="text-xl font-bold text-white mb-4">Exportar Copia de Seguridad</h2>
        <p class="text-white/60 max-w-lg mx-auto mb-8">
            Al solicitar la exportación, el sistema empaquetará todos los archivos PDF 
            pertenecientes a esta empresa en un único archivo ZIP para que puedas descargarlo.
            <strong>Este proceso quedará registrado en los logs de auditoría de seguridad.</strong>
        </p>

        <button wire:click="downloadZip" class="btn btn-primary px-8 py-3 text-lg font-medium" 
                wire:loading.attr="disabled"
                @if($fileCount === 0) disabled @endif>
            <span wire:loading.remove wire:target="downloadZip">
                <i class="ri-download-cloud-2-line mr-2"></i> Descargar Copia (.zip)
            </span>
            <span wire:loading wire:target="downloadZip">
                <i class="ri-loader-4-line animate-spin mr-2"></i> Preparando archivo...
            </span>
        </button>
    </div>
</div>
