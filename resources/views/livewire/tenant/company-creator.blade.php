<div>

    {{-- ── Mensajes flash ──────────────────────────────────────────────────── --}}
    @if (session()->has('message'))
        <div class="mb-6 flex items-start gap-3 rounded-xl px-4 py-3 text-sm"
             style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: var(--success);">
            <i class="ri-checkbox-circle-line mt-0.5 flex-shrink-0 text-base"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 flex items-start gap-3 rounded-xl px-4 py-3 text-sm"
             style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: var(--danger);">
            <i class="ri-error-warning-line mt-0.5 flex-shrink-0 text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         Formulario de creación — Mobile-First
         ══════════════════════════════════════════════════════════════════════ --}}
    <div class="glass-panel mb-6 p-5 md:p-7">

        <div class="mb-5 flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                 style="background: rgba(99,102,241,0.18);">
                <i class="ri-building-line text-lg" style="color: #a5b4fc;"></i>
            </div>
            <div>
                <h3 class="m-0 text-base font-semibold">Nueva Empresa / Sub-Empresa</h3>
                <p class="m-0 text-xs" style="color: var(--text-secondary);">
                    Se generará automáticamente un certificado digital (.pfx) para firmar los recibos.
                </p>
            </div>
        </div>

        <form wire:submit.prevent="save" novalidate>

            {{-- ── Grid responsive: 1 col en móvil, 2 col en ≥ md ────────── --}}
            <div class="grid grid-cols-1 gap-x-5 gap-y-0 md:grid-cols-2">

                {{-- Razón Social — ocupa ambas columnas en pantallas grandes --}}
                <div class="form-group md:col-span-2">
                    <label class="form-label">
                        Razón Social <span style="color: var(--danger);">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="name"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="form-control"
                        placeholder="Ej: Distribuidora Norte S.A."
                        autocomplete="organization"
                        maxlength="255"
                    >
                    @error('name')
                        <span class="mt-1 block text-xs" style="color: var(--danger);">{{ $message }}</span>
                    @enderror
                </div>

                {{-- CUIT --}}
                <div class="form-group">
                    <label class="form-label">
                        CUIT del Empleador <span style="color: var(--danger);">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="cuit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="form-control"
                        placeholder="Ej: 30712345678"
                        inputmode="numeric"
                        maxlength="11"
                    >
                    <small class="mt-1 block text-xs" style="color: var(--text-secondary);">
                        11 dígitos sin guiones (prefijo 20/23/24/27/30/33/34).
                    </small>
                    @error('cuit')
                        <span class="mt-1 block text-xs" style="color: var(--danger);">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Indicador de qué pasará al guardar --}}
                <div class="form-group flex items-start gap-2.5 rounded-lg p-3 md:mt-6"
                     style="padding: 10px;background: rgba(99,102,241,0.07); border: 1px solid rgba(99,102,241,0.18); align-self: start;">
                    <i class="ri-shield-keyhole-line flex-shrink-0 text-base mt-0.5" style="color: #a5b4fc;"></i>
                    <p class="m-0 text-xs leading-relaxed" style="color: var(--text-secondary);">
                        Al guardar, el sistema generará automáticamente un <strong style="color: white;">certificado RSA 2048-bit</strong>
                        válido por 5 años y lo vinculará a esta empresa.
                    </p>
                </div>
            </div>

            {{-- ── Botones de acción ───────────────────────────────────────── --}}
            {{-- w-full en móvil, ancho automático en md+ (Mobile-First) --}}
            <div class="mt-6 flex flex-col gap-3 md:flex-row md:justify-end">
                <a href="{{ route('dashboard') }}"
                   class="btn w-full justify-center md:w-auto"
                   style="background: rgba(255,255,255,0.07);">
                    Cancelar
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="btn btn-primary w-full justify-center md:w-auto"
                    style="background: var(--accent); min-width: 190px;"
                >
                    {{-- Estado normal --}}
                    <span wire:loading.remove wire:target="save">
                        <i class="ri-building-line mr-1.5"></i>
                        Crear Empresa y Certificado
                    </span>
                    {{-- Estado de carga — OpenSSL puede tardar ~0.5-1s --}}
                    <span wire:loading wire:target="save">
                        <i class="ri-loader-4-line mr-1.5"
                           style="display: inline-block; animation: spin 0.9s linear infinite;"></i>
                        Generando certificado…
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         Lista de empresas existentes
         ══════════════════════════════════════════════════════════════════════ --}}
    <div class="glass-panel p-5 md:p-7">

        <h4 class="mb-4 mt-0 flex items-center gap-2 text-sm font-semibold"
            style="color: var(--text-secondary);">
            <i class="ri-list-check-2 text-base"></i>
            Empresas registradas en este tenant ({{ $companies->count() }})
        </h4>

        @if ($companies->isEmpty())
            <p class="py-8 text-center text-sm" style="color: var(--text-secondary);">
                Aún no hay empresas registradas.
            </p>
        @else
            {{-- Tarjetas en móvil, tabla implícita con grid en desktop --}}
            <div class="flex flex-col gap-3">
                @foreach ($companies as $company)
                    @php
                        $hasCert    = ! empty($company->signature_pfx_path);
                        $isExpiring = $hasCert
                            && $company->signature_pfx_expires_at
                            && \Carbon\Carbon::parse($company->signature_pfx_expires_at)->diffInDays(now()) <= 30;
                    @endphp

                    <div class="flex flex-col gap-2 rounded-xl p-4 sm:flex-row sm:items-center sm:gap-4"
                         style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border);">

                        {{-- Avatar inicial --}}
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-base font-bold"
                             style="background: rgba(99,102,241,0.2); color: #a5b4fc;">
                            {{ strtoupper(mb_substr($company->name, 0, 1)) }}
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="font-semibold">{{ $company->name }}</span>
                                @if ($company->is_main)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                          style="background: rgba(99,102,241,0.2); color: #a5b4fc;">
                                        Principal
                                    </span>
                                @endif
                            </div>
                            <div class="mt-0.5 text-xs" style="color: var(--text-secondary);">
                                CUIT: {{ $company->cuit }}
                            </div>
                        </div>

                        {{-- Estado del certificado --}}
                        <div class="flex-shrink-0 text-xs">
                            @if (! $hasCert)
                                <span class="flex items-center gap-1" style="color: var(--warning);">
                                    <i class="ri-error-warning-line"></i> Sin certificado
                                </span>
                            @elseif ($isExpiring)
                                <span class="flex items-center gap-1" style="color: var(--warning);">
                                    <i class="ri-time-line"></i>
                                    Vence {{ \Carbon\Carbon::parse($company->signature_pfx_expires_at)->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="flex items-center gap-1" style="color: var(--success);">
                                    <i class="ri-shield-check-line"></i>
                                    Certificado activo
                                    @if ($company->signature_pfx_expires_at)
                                        · hasta {{ \Carbon\Carbon::parse($company->signature_pfx_expires_at)->format('d/m/Y') }}
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
