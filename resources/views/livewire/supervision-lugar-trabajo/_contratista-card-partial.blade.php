<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-md">
    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-bold text-gray-900 dark:text-gray-100">{{ $contratista->razon_social }}</h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $contratista->rut }}</p>
    </div>

    <div class="p-4 space-y-4">
        @if(in_array('PERSONA', $filtroEntidadTipos) && $contratista->trabajadores->isNotEmpty())
            @include('livewire.supervision-lugar-trabajo._tabla-trabajadores-partial', [
                'trabajadores' => $contratista->trabajadores, 
                'mandanteId' => $mandanteId, 
                'filtroUoIds' => $filtroUoIds
            ])
        @endif
        
        {{-- Aquí se incluirían las tablas parciales para Vehículos, Maquinarias, etc., siguiendo el mismo patrón. --}}
        {{-- Por ahora, solo implementamos Trabajadores como fue solicitado. --}}
    </div>
</div>