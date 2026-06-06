<div x-data="{ open: false }" class="relative">

    {{-- Trigger button --}}
    <button @click="open = !open"
            class="flex items-center gap-2 px-3 py-1.5
                   rounded-lg bg-white/10 hover:bg-white/15 transition-colors
                   text-sm font-medium text-white border border-white/10"
            :class="open ? 'bg-white/15' : ''"
            type="button"
            style="max-width: 180px;">
        <i class="ri-building-2-line text-accent shrink-0"></i>
        <span class="truncate flex-1 text-left">{{ $currentCompanyName }}</span>
        <i class="ri-arrow-down-s-line shrink-0 transition-transform duration-200"
           :class="open ? 'rotate-180' : ''"></i>
    </button>

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
         style="display:none; position:fixed; z-index:9999; width:260px; top:0; right:0;">

        <div class="px-3 py-2 border-b border-white/10">
            <p class="text-xs text-white/40 uppercase tracking-widest font-semibold whitespace-nowrap">
                Empresa activa
            </p>
        </div>

        @foreach($companies as $company)
        <button wire:click="switch({{ $company->id }})"
                @click="open = false"
                type="button"
                class="flex items-center gap-3 w-full px-4 py-3 text-sm
                       transition-colors text-left
                       {{ $currentCompanyId == $company->id
                            ? 'bg-white/10 text-white'
                            : 'text-white/60 hover:bg-white/5 hover:text-white' }}">

            {{-- Avatar inicial --}}
            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                         {{ $company->is_main ? 'bg-accent/20 text-accent' : 'bg-white/10 text-white/50' }}">
                {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
            </span>

            <div class="flex-1 min-w-0">
                <div class="font-medium truncate leading-tight">{{ $company->name }}</div>
                @if($company->cuit && !str_starts_with($company->cuit, 'PENDIENTE-'))
                    <div class="text-xs text-white/40 truncate">{{ $company->cuit }}</div>
                @endif
            </div>

            @if($company->is_main)
                <span class="shrink-0 text-xs text-accent/70 font-medium">Principal</span>
            @endif

            @if($currentCompanyId == $company->id)
                <i class="ri-check-line text-accent shrink-0 text-base"></i>
            @endif
        </button>
        @endforeach

    </div>
</div>
