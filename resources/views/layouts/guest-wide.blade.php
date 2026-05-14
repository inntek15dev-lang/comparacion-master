<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
    </head>
    <body class="font-sans text-gray-900 antialiased">
        
        <div class="relative min-h-screen bg-gray-900">
            
            <!-- IMAGEN DE FONDO -->
            <img 
                src="{{ asset('images/PRINCIPAL.jpg') }}" 
                alt="Fondo Principal del Proyecto" 
                class="absolute inset-0 w-full h-full object-cover z-0"
            >
            
            {{-- Capa oscura semitransparente --}}
            <div class="absolute inset-0 bg-black/30 z-1"></div>

            {{-- <<< INICIO DE LA MODIFICACIÓN CANÓNICA >>> --}}
            {{-- Contenedor principal que centra el contenido vertical y horizontalmente --}}
            <div class="relative min-h-screen flex flex-col items-center justify-center z-10 py-12 px-4 sm:px-6 lg:px-8">
                {{-- Contenedor del formulario con el nuevo ancho máximo --}}
                <div class="w-full sm:max-w-7xl">
                    {{ $slot }}
                </div>
            </div>
            {{-- <<< FIN DE LA MODIFICACIÓN CANÓNICA >>> --}}
            
        </div>
        @include('partials.cookie-banner')
    </body>
</html>