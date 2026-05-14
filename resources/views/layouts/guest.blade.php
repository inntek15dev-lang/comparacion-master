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
        @stack('styles')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        
        <div class="relative min-h-screen bg-gray-900">
            
            <!-- IMAGEN COMO FONDO COMPLETO -->
            <img 
                src="{{ asset('images/PRINCIPAL.jpg') }}" 
                alt="Fondo Principal del Proyecto" 
                class="absolute inset-0 w-full h-full object-cover z-0"
            >
            
            {{-- Capa oscura semitransparente para legibilidad --}}
            <div class="absolute inset-0 bg-black/30 z-1"></div>

            {{-- Contenedor principal que permite que el contenido se desplace si es necesario --}}
            <div class="relative z-10 min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/10 backdrop-blur-md shadow-2xl overflow-hidden sm:rounded-xl border border-white/20">
                    {{ $slot }}
                </div>
            </div>
            
        </div>
        @include('partials.cookie-banner')
        @stack('scripts')
    </body>
</html>