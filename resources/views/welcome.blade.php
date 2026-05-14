<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Mi Proyecto</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">

        <div class="relative min-h-screen bg-gray-900">
            
            <!-- 1. LA IMAGEN COMO FONDO COMPLETO (SIN CAMBIOS) -->
            <img 
                src="{{ asset('images/PRINCIPAL.jpg') }}" 
                alt="Fondo Principal del Proyecto" 
                class="absolute inset-0 w-full h-full object-cover z-0"
            >
            
            <!-- ================================================================== -->
            <!-- INICIO DE LA REFACTORIZACIÓN CANÓNICA: CONTENEDOR DE ACCIONES       -->
            <!-- ================================================================== -->
            <!-- Este único DIV ahora controla la posición y el layout de ambos botones. -->
            <div class="absolute top-0 right-0 p-8 z-10 flex flex-col items-end space-y-4">
                
                <!-- 2. EL BOTÓN "INGRESAR AL SISTEMA" (REFACTORIZADO) -->
                <!-- Texto cambiado, ruta confirmada, y estilos unificados. -->
                <a href="{{ route('login') }}" class="transform transition-transform duration-300 hover:scale-105 px-8 py-4 bg-green-600 text-white text-xl font-bold rounded-lg shadow-2xl hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-50 text-center">
                    Ingresar al Sistema
                </a>

                <!-- 3. EL BOTÓN DE INSCRIPCIÓN (REUBICADO) -->
                <!-- Movido desde el centro de la página a este contenedor. -->
                <button 
                    onclick="Livewire.dispatch('abrir-modal-inscripcion')"
                    class="transform transition-transform duration-300 hover:scale-105 px-8 py-4 bg-green-600 text-white text-xl font-bold rounded-lg shadow-2xl hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-50">
                    Inscripción Contratista Nuevo
                </button>

            </div>
            <!-- ================================================================== -->
            <!-- FIN DE LA REFACTORIZACIÓN CANÓNICA                                 -->
            <!-- ================================================================== -->


            <!-- El contenedor central del botón de inscripción ha sido ELIMINADO. -->


            <!-- El componente del modal de Livewire permanece (SIN CAMBIOS) -->
            @livewire('publico.inscripcion-modal')

        </div>
    </body>
</html>