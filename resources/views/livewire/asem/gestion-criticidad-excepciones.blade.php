<div>
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-bold mb-4">Gestión de Excepciones de Criticidad</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div>
                <label for="mandanteId" class="block text-sm font-medium text-gray-700">1. Principal</label>
                <select wire:model.live="mandanteId" id="mandanteId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">-- Seleccione --</option>
                    @foreach($mandantes as $mandante)
                        <option value="{{ $mandante['id'] }}">{{ $mandante['razon_social'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="contratistaId" class="block text-sm font-medium text-gray-700">2. Contratista</label>
                <select wire:model.live="contratistaId" id="contratistaId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($contratistas)) disabled @endif>
                    <option value="">-- Seleccione --</option>
                    @foreach($contratistas as $contratista)
                        <option value="{{ $contratista['id'] }}">{{ $contratista['razon_social'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="unidadOrganizacionalId" class="block text-sm font-medium text-gray-700">3. U. Organizacional</label>
                <select wire:model.live="unidadOrganizacionalId" id="unidadOrganizacionalId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($unidadesOrganizacionales)) disabled @endif>
                    <option value="">-- Seleccione --</option>
                    @foreach($unidadesOrganizacionales as $uo)
                        <option value="{{ $uo['id'] }}">{{ $uo['nombre_unidad'] }}</option>
                    @endforeach
                </select>
            </div>
             <div>
                <label for="tipoEntidadId" class="block text-sm font-medium text-gray-700">4. Tipo Entidad</label>
                <select wire:model.live="tipoEntidadId" id="tipoEntidadId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($tiposEntidad)) disabled @endif>
                    <option value="">-- Todas --</option>
                    @foreach($tiposEntidad as $tipo)
                        <option value="{{ $tipo['id'] }}">{{ $tipo['nombre_entidad'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="activoId" class="block text-sm font-medium text-gray-700">5. Activo Específico</label>
                <select wire:model.live="activoId" id="activoId" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($activos)) disabled @endif>
                    <option value="">-- Todos (Global de Entidad) --</option>
                     @foreach($activos as $activo)
                        <option value="{{ $activo['id'] }}">{{ $activo['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div wire:loading wire:target="mandanteId, contratistaId, unidadOrganizacionalId, tipoEntidadId, activoId" class="text-center w-full py-4">Cargando...</div>

        <div wire:loading.remove wire:target="mandanteId, contratistaId, unidadOrganizacionalId, tipoEntidadId, activoId" class="overflow-x-auto">
            @if(empty($documentosConCriticidad))
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">Seleccione los filtros para configurar las excepciones.</p>
                </div>
            @else
                @include('livewire.asem.partials.criticidad-excepciones-tabla')
            @endif
        </div>
    </div>
</div>