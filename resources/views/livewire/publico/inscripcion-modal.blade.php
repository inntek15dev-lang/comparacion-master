<div>
    @if ($showModal)
    <div 
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24"
        style="display: none;"
        @keydown.escape.window="show = false"
    >
        {{-- Fondo oscuro semitransparente --}}
        <div @click="show = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        {{-- Contenido del modal --}}
        <div 
            @click.away="show = false"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 space-y-4"
        >
            <h3 class="text-xl font-semibold text-center text-gray-800 dark:text-gray-200">Inscripción de Nueva Empresa Contratista</h3>
            
            <p class="text-sm text-center text-gray-600 dark:text-gray-400">
                Seleccione el Principal al que desea vincularse para comenzar el proceso de inscripción.
            </p>

            {{-- Solo mostrar selector de Mandante (ya no hay opción de sub-contratista) --}}
            <div class="pt-2 space-y-4">
                <div>
                    <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccione Principal</label>
                    <select wire:model.live="mandanteId" id="mandante_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                        <option value="">-- Seleccione una opción --</option>
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('mandanteId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-2">
                <button 
                    wire:click="validarYContinuar"
                    @php
                        $isDisabled = empty($mandanteId);
                    @endphp
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-opacity {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $isDisabled ? 'disabled' : '' }}
                >
                    <span wire:loading.remove wire:target="validarYContinuar">Continuar</span>
                    <span wire:loading wire:target="validarYContinuar">Validando...</span>
                </button>
            </div>

            <div class="pt-2 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    ¿Es usted un Sub-Contratista? Contacte a su Contratista principal para que lo registre desde su panel.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>