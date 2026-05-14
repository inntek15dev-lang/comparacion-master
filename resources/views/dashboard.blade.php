@php
    if (auth()->user()->hasRole('Operador_IA')) {
        header("Location: " . route('ia.extraccion'));
        exit;
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- INICIO DE LA MODIFICACIÓN: Se elimina el texto "You're logged in!" --}}
                    ¡Bienvenido a la plataforma de gestión documental!
                    

                    {{-- FIN DE LA MODIFICACIÓN --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>