<div>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-2xl rounded-2xl border-2 border-blue-300 overflow-hidden ring-4 ring-blue-100">
                <div class="p-8">
                    <!-- ESTA LÍNEA HA SIDO PURGADA PARA ELIMINAR EL TÍTULO DUPLICADO -->
                    {{-- <h2 class="text-2xl font-bold text-blue-900 mb-6">FICHA EMPRESA</h2> --}}
                    @livewire('ficha-contratista', ['contratistaId' => auth()->user()->contratista_id])
                </div>
            </div>
        </div>
    </div>
</div>