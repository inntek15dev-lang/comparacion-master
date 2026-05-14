<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine: multiSelectCondicion — registrado globalmente antes de alpine:init -->
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('multiSelectCondicion', ({ allOptions, initSelected, wireKey }) => ({
            allOptions,
            selected: [...(initSelected || [])],
            search:   '',
            open:     false,
            wireKey,

            init() {
                // Sincronizar con Livewire cuando cambia la selección
                // wire:ignore previene morphing; este watch persiste el valor al submit
                this.$watch('selected', (val) => {
                    // Usar $wire si está disponible (Livewire 3 magic)
                    if (typeof this.$wire !== 'undefined') {
                        this.$wire.set(this.wireKey, val);
                    } else {
                        // Fallback: buscar el componente Livewire padre
                        const el = this.$el.closest('[wire\\:id]');
                        if (el) {
                            const component = Livewire.find(el.getAttribute('wire:id'));
                            if (component) component.set(this.wireKey, val);
                        }
                    }
                });
            },

            toggleOpen() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => {
                        const input = this.$el.querySelector('input[type="text"]');
                        if (input) input.focus();
                    });
                }
            },

            filteredOptions() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.allOptions;
                return this.allOptions.filter(o => o.nombre.toLowerCase().includes(q));
            },

            selectedItems() {
                return this.allOptions.filter(o => this.selected.includes(o.id));
            },

            isSelected(id) { return this.selected.includes(id); },

            toggle(id) {
                if (this.isSelected(id)) {
                    this.selected = this.selected.filter(s => s !== id);
                } else {
                    this.selected = [...this.selected, id];
                }
            },

            remove(id) { this.selected = this.selected.filter(s => s !== id); },

            clearAll() { this.selected = []; }
        }));
    });
    </script>
    
    <!-- Prevenir flash de tema incorrecto -->
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="font-sans antialiased">
    <div x-data="{ sidebarOpen: false, sidebarCollapsed: true }" class="min-h-screen bg-gray-100 dark:bg-gray-900">
        
        <livewire:layout.navigation />

        <div class="flex flex-col min-h-screen transition-all duration-300 ease-in-out"
             :class="{ 'lg:ml-20': sidebarCollapsed, 'lg:ml-64': !sidebarCollapsed }">
            
            <main class="flex-grow">
                {{ $slot }}
            </main>
        </div>

        {{-- Componente de Popups para usuarios autenticados --}}
        @auth
            <livewire:popup-modal />
        @endauth

        @include('partials.cookie-banner')
    </div>
    @stack('scripts')
</body>
</html>