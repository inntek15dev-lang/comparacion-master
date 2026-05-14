{{--
 | Single-Select con Buscador — Componente Alpine.js reutilizable
 |
 | Props Blade:
 |   $opciones       → colección Eloquent / array  con ->id y ->nombre
 |   $seleccionado   → ID único ya seleccionado (scalar)
 |   $wireKey        → string con el path Livewire a sincronizar
 |                     Ej: "criterios.0.criterio_evaluacion_id"
 |   $placeholder    → texto del input buscador (default: "Buscar...")
 |   $label          → etiqueta visible (opcional)
 |   $isCustomLabel  → bool si se usa etiqueta custom por fuera
 --}}
@php
    $placeholder ??= 'Buscar...';
    $label       ??= null;
    $wireKey     ??= '';

    // Compatible con colección Eloquent Y array PHP plano
    $opcionesArray = [];
    foreach (($opciones ?? []) as $o) {
        if (is_array($o)) {
            $opcionesArray[] = ['id' => (string)$o['id'], 'nombre' => $o['nombre'] ?? $o['titulo'] ?? $o['nombre_criterio'] ?? ''];
        } else {
            $opcionesArray[] = ['id' => (string)$o->id, 'nombre' => $o->nombre ?? $o->titulo ?? $o->nombre_criterio ?? ''];
        }
    }
    $opcionesJson = json_encode(array_values($opcionesArray), JSON_UNESCAPED_UNICODE);
    $seleccionadoString = $seleccionado ? "'".(string)$seleccionado."'" : "null";
@endphp

<div
    wire:ignore
    x-data="{
        allOptions: {{ $opcionesJson }},
        selected: {{ $seleccionadoString }},
        search: '',
        open: false,
        wireKey: '{{ $wireKey }}',
        
        init() {
            this.$watch('selected', (val) => {
                if (typeof this.$wire !== 'undefined') {
                    this.$wire.set(this.wireKey, val);
                }
            });
            // Update selected safely when Livewire refreshes it externally
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    let path = this.wireKey.split('.');
                    let val = snapshot.data;
                    for (let step of path) {
                        if (val && typeof val === 'object') {
                            val = val[step];
                        } else {
                            val = undefined;
                        }
                    }
                    if (val !== undefined && val !== this.selected) {
                        this.selected = val ? String(val) : null;
                    }
                })
            })
        },
        get selectedName() {
            if (!this.selected) return '';
            let found = this.allOptions.find(o => o.id === this.selected);
            return found ? found.nombre : '';
        },
        get filteredOptions() {
            if (this.search === '') return this.allOptions;
            let lowerSearch = this.search.toLowerCase();
            return this.allOptions.filter(o => o.nombre.toLowerCase().includes(lowerSearch));
        },
        toggleOpen() {
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                $nextTick(() => { this.$refs.searchInput.focus(); });
            }
        },
        selectOption(id) {
            this.selected = String(id);
            this.open = false;
        },
        clearSelection() {
            this.selected = null;
            this.open = false;
        }
    }"
    x-init="init()"
    @click.outside="open = false"
    class="relative"
>
    {{-- Label opcional --}}
    @if($label)
        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">
            {{ $label }}
        </label>
    @endif

    {{-- Trigger / Campo principal --}}
    <div
        @click="toggleOpen()"
        class="min-h-[34px] w-full cursor-pointer flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 focus-within:ring-1 focus-within:ring-indigo-500 transition"
    >
        {{-- Seleccionado --}}
        <span x-show="selected" class="flex-grow text-sm text-gray-800 dark:text-gray-200 block truncate" x-text="selectedName"></span>
        
        {{-- Placeholder cuando no hay asignado --}}
        <span x-show="!selected" class="flex-grow text-xs text-gray-400 dark:text-gray-500 italic outline-none select-none block">{{ $placeholder }}</span>

        {{-- Icono limpiar --}}
        <button 
            x-show="selected" 
            type="button" 
            @click.stop="clearSelection()" 
            class="text-gray-400 hover:text-red-500 mx-1 transition leading-none focus:outline-none focus:ring-0" 
            title="Quitar"
        >✕</button>

        {{-- Icono chevron --}}
        <span class="ml-1 text-gray-400 dark:text-gray-500 text-[10px] select-none" x-text="open ? '▲' : '▼'"></span>
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
        <div class="p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-md">
            <input
                x-ref="searchInput"
                type="text"
                x-model="search"
                @click.stop
                placeholder="{{ $placeholder }}"
                class="w-full text-xs px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
        </div>

        {{-- Lista filtrada --}}
        <ul class="max-h-48 overflow-y-auto py-1">
            <template x-for="option in filteredOptions" :key="option.id">
                <li
                    @click.stop="selectOption(option.id)"
                    class="flex items-center gap-2 px-3 py-1.5 text-xs cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                    :class="selected === option.id ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-gray-700 dark:text-gray-300'"
                >
                    <span
                        class="flex-shrink-0 w-3 h-3 rounded flex items-center justify-center text-[8px] font-bold transition"
                        :class="selected === option.id
                            ? 'text-indigo-600'
                            : 'text-transparent'"
                    >✓</span>
                    <span x-text="option.nombre" class="break-words line-clamp-2"></span>
                </li>
            </template>

            {{-- Sin resultados --}}
            <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-xs text-gray-400 italic text-center">
                Sin resultados para "<span x-text="search"></span>"
            </li>
        </ul>
    </div>
</div>
