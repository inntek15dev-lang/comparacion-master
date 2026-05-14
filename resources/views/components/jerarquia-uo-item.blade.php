@props([
    'uo',
    'level',
    'selectedUOsConCondicion',
    'tiposCondicionDisponibles',
])

@php
    $prefixedUoKey = 'uo_' . $uo->id;
    $parentIdentifier = $uo->parent_id ?? 'root';
    $itemWireKey = "jerarquia-item-{$parentIdentifier}-{$uo->id}-level-{$level}";
@endphp

<div class="space-y-1" wire:key="{{ $itemWireKey }}">
    <div class="grid grid-cols-12 gap-x-2 items-start py-1 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
        
        <div class="col-span-7 flex items-center pt-1" style="padding-left: {{ $level * 1.5 }}rem;">
            <input type="checkbox"
                   id="uo_cond_sel_{{ $uo->id }}"
                   value="{{ $uo->id }}"
                   wire:change="$dispatch('toggleUOCondicionEvent', { uoId: {{ $uo->id }}, isChecked: $event.target.checked })"
                   class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mr-2 shrink-0"
                   {{ array_key_exists($prefixedUoKey, $selectedUOsConCondicion ?? []) ? 'checked' : '' }}>
            <label for="uo_cond_sel_{{ $uo->id }}" class="text-sm text-gray-800 dark:text-gray-200 truncate" title="{{ $uo->nombre_unidad }}">
                {{ $uo->nombre_unidad }}
            </label>
        </div>

        <div class="col-span-5">
            @if(array_key_exists($prefixedUoKey, $selectedUOsConCondicion ?? []))
                <select 
                        {{-- CAMBIO CLAVE: Eliminar wire:model.lazy y usar wire:change para emitir evento --}}
                        wire:change="$dispatch('condicionUoCambiada', { uoId: {{ $uo->id }}, tipoCondicionId: $event.target.value })"
                        class="input-field input-field-sm py-1 text-xs 
                               focus:ring-indigo-500 focus:border-indigo-500
                               {{ ($selectedUOsConCondicion[$prefixedUoKey] ?? null) === null ? 'border-yellow-400 dark:border-yellow-500' : 'border-gray-300 dark:border-gray-600' }}">
                    <option value="">-- Sin Condición --</option>
                    @if(isset($tiposCondicionDisponibles))
                        @foreach($tiposCondicionDisponibles as $condicion)
                            {{-- Para que el select muestre el valor correcto, necesitamos comparar con el estado actual --}}
                            <option value="{{ $condicion->id }}" {{ ($selectedUOsConCondicion[$prefixedUoKey] ?? null) == $condicion->id ? 'selected' : '' }}>
                                {{ $condicion->nombre }}
                            </option>
                        @endforeach
                    @endif
                </select>
                 {{-- El error se sigue mostrando para la propiedad del padre, lo cual está bien --}}
                 @error('selectedUnidadesConCondicion.' . $prefixedUoKey) <span class="error-message text-xxs">{{ $message }}</span> @enderror
            @else
                <select class="input-field input-field-sm py-1 text-xs border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 cursor-not-allowed" disabled>
                    <option value="">-- N/A --</option>
                </select>
            @endif
        </div>
    </div>

    @if(isset($uo->children_uos) && $uo->children_uos->count() > 0)
        @foreach($uo->children_uos as $childUo)
            <x-jerarquia-uo-item
                :uo="$childUo"
                :level="$level + 1"
                :selectedUOsConCondicion="$selectedUOsConCondicion"
                :tiposCondicionDisponibles="$tiposCondicionDisponibles"
                wire:key="jerarquia-item-parent{{ $uo->id }}-child{{ $childUo->id }}-level{{ $level + 1 }}"
            />
        @endforeach
    @endif
</div>