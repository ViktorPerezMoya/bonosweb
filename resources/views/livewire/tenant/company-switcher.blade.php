<div x-data="{ open: false }" class="relative">

    {{-- Trigger button --}}
    @if($companies->isEmpty())
        <button disabled
                class="flex items-center gap-2 px-3 py-1.5
                       rounded-lg opacity-50 cursor-not-allowed
                       text-sm font-medium"
                style="background: var(--input-bg); border: 1px solid var(--glass-border); color: var(--text-primary); max-width: 180px;"
                type="button">
            <i class="ri-building-2-line shrink-0" style="color: var(--text-secondary);"></i>
            <span class="truncate flex-1 text-left" style="color: var(--text-secondary);">Sin Empresa</span>
        </button>
    @else
        <button @click="open = !open"
                class="flex items-center gap-2 px-3 py-1.5
                       rounded-lg transition-colors
                       text-sm font-medium"
                :style="open ? 'background: var(--nav-hover); border: 1px solid var(--glass-border); color: var(--text-primary);' : 'background: var(--input-bg); border: 1px solid var(--glass-border); color: var(--text-primary);'"
                style="background: var(--input-bg); border: 1px solid var(--glass-border); color: var(--text-primary); max-width: 180px;"
                type="button">
            <i class="ri-building-2-line text-accent shrink-0"></i>
            <span class="truncate flex-1 text-left">{{ $currentCompanyName }}</span>
            <i class="ri-arrow-down-s-line shrink-0 transition-transform duration-200"
               :class="open ? 'rotate-180' : ''"></i>
        </button>
    @endif

    {{-- Dropdown
         Se posiciona con position:fixed para escapar de cualquier stacking context
         creado por transform en el header padre. Las coordenadas se calculan
         via Alpine al abrir.
    --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         @click.outside="open = false"
         x-init="
             $watch('open', value => {
                 if (value) {
                     const rect = $el.previousElementSibling.getBoundingClientRect();
                     $el.style.top  = (rect.bottom + 8) + 'px';
                     $el.style.right = (window.innerWidth - rect.right) + 'px';
                 }
             })
         "
         class="glass-panel shadow-2xl"
         style="display:none; position:fixed; z-index:9999; width:260px; top:0; right:0; padding: 0.5rem; background: var(--bg-secondary);">

        <div class="px-3 py-2" style="border-bottom: 1px solid var(--glass-border);">
            <p class="text-xs uppercase tracking-widest font-semibold whitespace-nowrap" style="color: var(--text-secondary);">
                Empresa activa
            </p>
        </div>

        @foreach($companies as $company)
        <button wire:click="switch({{ $company->id }})"
                @click="open = false"
                type="button"
                class="flex items-center gap-3 w-full px-4 py-3 text-sm
                       transition-colors text-left rounded-lg mt-1"
                style="{{ $currentCompanyId == $company->id ? 'background: var(--nav-hover); color: var(--text-primary);' : 'color: var(--text-secondary);' }}"
                onmouseover="this.style.background='var(--nav-hover)'; this.style.color='var(--text-primary)';"
                onmouseout="this.style.background='{{ $currentCompanyId == $company->id ? 'var(--nav-hover)' : 'transparent' }}'; this.style.color='{{ $currentCompanyId == $company->id ? 'var(--text-primary)' : 'var(--text-secondary)' }}';">

            {{-- Avatar inicial --}}
            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                  style="{{ $company->is_main ? 'background: rgba(59, 130, 246, 0.2); color: var(--accent);' : 'background: var(--glass-border); color: var(--text-primary);' }}">
                {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
            </span>

            <div class="flex-1 min-w-0">
                <div class="font-medium truncate leading-tight">{{ $company->name }}</div>
                @if($company->cuit && !str_starts_with($company->cuit, 'PENDIENTE-'))
                    <div class="text-xs truncate" style="color: var(--text-secondary);">{{ $company->cuit }}</div>
                @endif
            </div>

            @if($company->is_main)
                <span class="shrink-0 text-xs font-medium" style="color: var(--accent);">Principal</span>
            @endif

            @if($currentCompanyId == $company->id)
                <i class="ri-check-line text-accent shrink-0 text-base"></i>
            @endif
        </button>
        @endforeach

    </div>
</div>
