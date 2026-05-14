<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale-1">
        <title>OVAL Control</title>

        <!-- Fonts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-[#0a234f] text-white">
        
        {{-- NAVEGACIÓN PRINCIPAL --}}
        <header class="bg-black/20 backdrop-blur-lg sticky top-0 z-50">
            <nav class="container mx-auto flex items-center justify-between p-4">
                {{-- Logo --}}
                <a href="/" wire:navigate>
                    <img src="{{ asset('images/logo-oval-blanco.png') }}" alt="Logo OVAL" class="h-8">
                    {{-- Nota: Necesitarás un logo en versión blanca para que se vea bien --}}
                </a>
                
                {{-- Menú de Navegación --}}
                <div class="hidden md:flex space-x-6 text-sm font-semibold">
                    <a href="{{ route('home') }}" class="hover:text-gray-300 transition-colors">Inicio</a>
                    <a href="{{ route('about') }}" class="hover:text-gray-300 transition-colors">Quiénes Somos</a>
                    <a href="#" class="hover:text-gray-300 transition-colors">Servicios</a>
                    <a href="#" class="hover:text-gray-300 transition-colors">Experiencia</a>
                    <a href="#" class="hover:text-gray-300 transition-colors">Contacto</a>
                </div>

                {{-- Botón de Login --}}
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-semibold bg-white/10 rounded-md hover:bg-white/20 transition-colors">
                    Log In
                </a>
            </nav>
        </header>

        {{-- CONTENIDO DE CADA PÁGINA --}}
        <main>
            @yield('content')
        </main>

        {{-- PIE DE PÁGINA (OPCIONAL) --}}
        <footer class="text-center p-6 text-xs text-gray-400">
            © {{ date('Y') }} OVAL Control. Todos los derechos reservados.
        </footer>

    </body>
</html>