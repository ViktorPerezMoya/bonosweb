@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}
$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
    : '';
@endphp

@if ($paginator->hasPages())
<div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">

    {{-- Info --}}
    <span style="font-size: 0.8rem; color: var(--text-secondary);">
        Mostrando <strong style="color: var(--text-primary);">{{ $paginator->firstItem() }}</strong>
        al <strong style="color: var(--text-primary);">{{ $paginator->lastItem() }}</strong>
        de <strong style="color: var(--text-primary);">{{ $paginator->total() }}</strong> resultados
    </span>

    {{-- Botones --}}
    <div style="display: flex; align-items: center; gap: 0.35rem;">

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.82rem; color: var(--text-secondary); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); cursor: default; opacity: 0.5;">
                ‹ Anterior
            </span>
        @else
            <button type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.82rem; color: var(--text-primary); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); cursor: pointer; transition: background 0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)'"
                onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                ‹ Anterior
            </button>
        @endif

        {{-- Números de página --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="padding: 0.35rem 0.5rem; font-size: 0.82rem; color: var(--text-secondary);">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding: 0.35rem 0.65rem; border-radius: 6px; font-size: 0.82rem; font-weight: 600; color: white; background: var(--accent); border: 1px solid var(--accent);">
                            {{ $page }}
                        </span>
                    @else
                        <button type="button"
                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            style="padding: 0.35rem 0.65rem; border-radius: 6px; font-size: 0.82rem; color: var(--text-primary); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.12)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <button type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                wire:loading.attr="disabled"
                style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.82rem; color: var(--text-primary); background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); cursor: pointer; transition: background 0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)'"
                onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                Siguiente ›
            </button>
        @else
            <span style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.82rem; color: var(--text-secondary); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); cursor: default; opacity: 0.5;">
                Siguiente ›
            </span>
        @endif

    </div>
</div>
@endif
