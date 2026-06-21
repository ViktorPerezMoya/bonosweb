<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Motivos de Disconformidad</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">Configura las opciones que los empleados pueden seleccionar al firmar en disconformidad.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 rounded bg-green-500/20 border border-green-500/30 text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-4 rounded bg-red-500/20 border border-red-500/30 text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="glass-panel p-6 mb-6">
        <h2 class="text-lg font-medium mb-4" style="color: var(--text-primary);">Agregar Nuevo Motivo</h2>
        <form wire:submit.prevent="addReason" class="flex gap-4 items-start">
            <div class="flex-1">
                <input type="text" wire:model.defer="newReason" 
                       class="form-control w-full h-10 mt-2" 
                       placeholder="Ej. Diferencia en liquidación">
                @error('newReason') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                <i class="ri-add-line mr-1"></i> Agregar
            </button>
        </form>
    </div>

    <div class="glass-panel overflow-hidden">
        <table class="modern-table w-full text-left text-sm">
            <thead>
                <tr>
                    <th>Motivo</th>
                    <th class="w-32">Estado</th>
                    <th class="w-32 text-right" style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reasons as $reason)
                    <tr>
                        <td class="px-6 py-4">
                            @if($editingId === $reason->id)
                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="editingReasonText" 
                                           class="form-control w-full px-2 py-1 text-sm">
                                </div>
                                @error('editingReasonText') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            @else
                                {{ $reason->reason_text }}
                            @endif
                        </td>
                        <td class="px-6 py-4" style="vertical-align: middle;">
                            @if($reason->is_active)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                                    Activo
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right" style="vertical-align: middle;">
                            @if($editingId === $reason->id)
                                <button wire:click="updateReason" class="text-green-400 hover:text-green-300 mr-2" title="Guardar">
                                    <i class="ri-check-line text-lg"></i>
                                </button>
                                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-300" title="Cancelar">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            @else
                                <button wire:click="editReason({{ $reason->id }})" class="text-blue-400 hover:text-blue-300 mr-3" title="Editar">
                                    <i class="ri-edit-line text-lg"></i>
                                </button>
                                <button wire:click="toggleActive({{ $reason->id }})" class="text-gray-400 hover:text-gray-200" title="{{ $reason->is_active ? 'Deshabilitar' : 'Habilitar' }}">
                                    <i class="ri-toggle-line text-lg {{ $reason->is_active ? 'text-green-400' : 'text-gray-500' }}"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center" style="color: var(--text-secondary);">
                            No hay motivos de disconformidad configurados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
