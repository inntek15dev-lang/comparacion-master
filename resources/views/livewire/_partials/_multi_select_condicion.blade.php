{{--
 | Multi-Select con Buscador — Componente Alpine.js reutilizable
 |
 | Props Blade:
 |   $opciones       → colección Eloquent / array  con ->id y ->nombre
 |   $seleccionados  → array de IDs ya seleccionados
 |   $wireKey        → string con el path Livewire a sincronizar
 |                     Ej: "vinculacionesTemp.0.condiciones_ids"  /  "v_condiciones_personales_ids"
 |   $placeholder    → texto del input buscador (default: "Buscar condición...")
 |   $label          → etiqueta visible (default: "Condición")
 --}}
@php
    $placeholder ??= 'Buscar condición...';
    $label       ??= 'Condición';
    $wireKey     ??= 'v_condiciones_ids';


    // Compatible con colección Eloquent Y array PHP plano
    $opcionesArray = [];
    foreach (($opciones ?? []) as $o) {
        if (is_array($o)) {
            $opcionesArray[] = ['id' => (int)$o['id'], 'nombre' => $o['nombre']];
        } else {
            $opcionesArray[] = ['id' => (int)$o->id, 'nombre' => $o->nombre];
        }
    }
    $opcionesJson      = json_encode(array_values($opcionesArray), JSON_UNESCAPED_UNICODE);
    $seleccionadosJson = json_encode(array_map('intval', $seleccionados ?? []));
@endphp

<div
    wire:key="{{ $wireKey }}-{{ md5($opcionesJson) }}"
    wire:ignore
    x-data="multiSelectCondicion({
        allOptions:    {{ $opcionesJson }},
        initSelected:  {{ $seleccionadosJson }},
        wireKey:       '{{ $wireKey }}'
    })"
    x-init="init()"
    @click.outside="open = false"
    class="relative"
>
    {{-- Label --}}
    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">
        {{ $label }}
    </label>

    {{-- Trigger / Campo principal --}}
    <div
        @click="toggleOpen()"
        class="min-h-[34px] w-full cursor-pointer flex flex-wrap gap-1 items-center px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition"
    >
        {{-- Tags de seleccionados --}}
        <template x-for="item in selectedItems()" :key="item.id">
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-100 text-[10px] font-semibold rounded-full">
                <span x-text="item.nombre"></span>
                <button
                    type="button"
                    @click.stop="remove(item.id)"
                    class="text-indigo-500 dark:text-indigo-300 hover:text-red-500 dark:hover:text-red-400 leading-none transition"
                    title="Quitar"
                >✕</button>
            </span>
        </template>

        {{-- Placeholder cuando no hay seleccionados --}}
        <span
            x-show="selected.length === 0"
            class="text-[10px] text-gray-400 dark:text-gray-500 italic select-none"
        >Sin condición asignada</span>

        {{-- Icono chevron --}}
        <span class="ml-auto text-gray-400 dark:text-gray-500 text-xs select-none" x-text="open ? '▲' : '▼'"></span>
    </div>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-[999] mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg"
        style="display:none;"
    >
        {{-- Input buscador --}}
        <div class="p-2 border-b border-gray-200 dark:border-gray-700">
            <input
                type="text"
                x-model="search"
                @click.stop
                placeholder="{{ $placeholder }}"
                class="w-full text-xs px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
        </div>

        {{-- Lista filtrada --}}
        <ul class="max-h-48 overflow-y-auto py-1">
            <template x-for="option in filteredOptions()" :key="option.id">
                <li
                    @click.stop="toggle(option.id)"
                    class="flex items-center gap-2 px-3 py-1.5 text-xs cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                    :class="isSelected(option.id) ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300'"
                >
                    <span
                        class="flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center text-[9px] font-bold transition"
                        :class="isSelected(option.id)
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-400 dark:border-gray-500 text-transparent'"
                    >✓</span>
                    <span x-text="option.nombre"></span>
                </li>
            </template>

            {{-- Sin resultados --}}
            <li x-show="filteredOptions().length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                Sin resultados para "<span x-text="search"></span>"
            </li>
        </ul>

        {{-- Footer: limpiar todo --}}
        <div x-show="selected.length > 0" class="px-3 py-1.5 border-t border-gray-100 dark:border-gray-700 flex justify-end">
            <button
                type="button"
                @click.stop="clearAll()"
                class="text-[10px] text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-semibold transition"
            >✕ Limpiar todo</button>
        </div>
    </div>
</div>

{{-- La función Alpine `multiSelectCondicion` está registrada globalmente en layouts/app.blade.php y layouts/operacion-layout.blade.php --}}
