<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mis Sub-Contratistas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8" style="width: 95%; max-width: 98%;">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <!-- Buscador y Botón -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3">
                        <input wire:model.live="search" type="text" placeholder="Buscar por Razón Social o RUT..." class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    </div>
                    <div>
                        <a href="{{ route('contratista.solicitar-sub-contratista') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nuevo Sub-Contratista
                        </a>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="py-3 px-6">Razón Social</th>
                                <th scope="col" class="py-3 px-6">RUT / NIT</th>
                                <th scope="col" class="py-3 px-6">Email Empresa</th>
                                <th scope="col" class="py-3 px-6">Usuario Admin</th>
                                <th scope="col" class="py-3 px-6">Estado</th>
                                <th scope="col" class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subcontratistas as $contratista)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        @php
                                            $jerarquia = collect([]);
                                            
                                            // 1. El Hijo (Subcontratista actual de la fila) va PRIMERO
                                            $jerarquia->push($contratista);
                                            
                                            // 2. Buscar ancestros hacia arriba
                                            $padre = $contratista->contratistaPadreAprobado->first();
                                            $limit = 0;
                                            while($padre && $limit < 5) {
                                                // Si llegamos al contratista logueado (Raíz de la vista), paramos.
                                                // El usuario "ya sabe que es su subcontratista", no necesita verse a sí mismo.
                                                if ($padre->id === $contratistaActual->id) {
                                                    break; 
                                                }

                                                $jerarquia->push($padre);
                                                $padre = $padre->contratistaPadreAprobado->first(); 
                                                $limit++;
                                            }
                                        @endphp

                                        <div class="flex flex-col">
                                            @foreach($jerarquia as $item)
                                                <div class="flex items-center {{ $loop->first ? 'text-sm font-bold text-gray-900 dark:text-white' : 'text-xs text-gray-500 mt-0.5' }}">
                                                    @if(!$loop->first) 
                                                        <span class="mr-1 ml-2">↳ Depende de:</span> 
                                                    @endif
                                                    {{ $item->razon_social }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        {{ $contratista->rut }}
                                    </td>
                                    <td class="py-4 px-6">
                                        {{ $contratista->email_empresa }}
                                        <div class="text-xs text-gray-500">{{ $contratista->telefono_empresa }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        @forelse($contratista->users as $user)
                                            <div class="mb-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                                <div class="text-xs text-blue-600 dark:text-blue-400">{{ $user->email }}</div>
                                            </div>
                                        @empty
                                            <span class="text-xs text-gray-400 italic">No asignado</span>
                                        @endforelse
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($contratista->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <button wire:click="abrirModalVinculaciones({{ $contratista->id }}, null)" class="font-medium text-blue-600 dark:text-blue-500 hover:underline block mx-auto mb-2">
                                            Ver Vinculaciones
                                        </button>
                                        <button wire:click="gestionarUsuarios({{ $contratista->id }})" class="font-medium text-purple-600 dark:text-purple-500 hover:underline block mx-auto">
                                            Gestionar Usuarios
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="5" class="py-4 px-6 text-center text-gray-500 dark:text-gray-400">
                                        No tienes sub-contratistas aprobados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $subcontratistas->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Vinculaciones -->
    @if($showModalVinculaciones)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none bg-gray-900 bg-opacity-50">
        <div class="relative w-auto max-w-4xl mx-auto my-6 p-0 bg-white rounded-lg shadow-lg dark:bg-gray-800 w-full">
            <!-- Header -->
            <div class="flex items-start justify-between p-5 border-b border-solid border-gray-200 rounded-t dark:border-gray-700">
                <h3 class="text-2xl font-semibold dark:text-white">
                    Vinculaciones de {{ $subContratistaSeleccionado->razon_social ?? '' }}
                </h3>
                <button wire:click="cerrarModalVinculaciones" class="p-1 ml-auto bg-transparent border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none dark:text-white">
                    <span class="bg-transparent text-black dark:text-white h-6 w-6 text-2xl block outline-none focus:outline-none">×</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="relative p-6 flex-auto max-h-[70vh] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Principal (Mandante)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Unidad / Dependencia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Heredado de</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @forelse($vinculacionesSub as $vinc)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    @if($vinc->unidadOrganizacionalMandante)
                                        {{ $vinc->unidadOrganizacionalMandante->mandante->razon_social ?? '-' }}
                                    @elseif($vinc->dependencia)
                                        {{ $vinc->dependencia->mandante->razon_social ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($vinc->unidadOrganizacionalMandante)
                                        UO: {{ $vinc->unidadOrganizacionalMandante->nombre_unidad }}
                                    @elseif($vinc->dependencia)
                                        Dep: {{ $vinc->dependencia->nombre }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($vinc->contratista_padre_vinculacion_id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Vinculación Padre #{{ $vinc->contratista_padre_vinculacion_id }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Asignación Directa
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center dark:text-gray-400">
                                    No hay vinculaciones activas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-end p-6 border-t border-solid border-gray-200 rounded-b dark:border-gray-700">
                <button wire:click="cerrarModalVinculaciones" class="text-gray-500 background-transparent font-bold uppercase px-6 py-2 text-sm outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif


    <!-- Modal de Gestión de Usuarios -->
    @if($showModalUsuarios)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none bg-gray-900 bg-opacity-50">
        <div class="relative w-auto max-w-4xl mx-auto my-6 p-0 bg-white rounded-lg shadow-lg dark:bg-gray-800 w-full">
            <!-- Header -->
            <div class="flex items-start justify-between p-5 border-b border-solid border-gray-200 rounded-t dark:border-gray-700">
                <h3 class="text-2xl font-semibold dark:text-white">
                    Usuarios de {{ $subContratistaSeleccionado->razon_social ?? '' }}
                </h3>
                <button wire:click="cerrarModalUsuarios" class="p-1 ml-auto bg-transparent border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none dark:text-white">
                    <span class="bg-transparent text-black dark:text-white h-6 w-6 text-2xl block outline-none focus:outline-none">×</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="relative p-6 flex-auto max-h-[70vh] overflow-y-auto">
                
                @if($showFormUsuario)
                    <!-- Formulario de Creación/Edición -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h4 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">{{ $userForm['id'] ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</h4>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                                <input wire:model="userForm.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                @error('userForm.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input wire:model="userForm.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                @error('userForm.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña {{ $userForm['id'] ? '(Dejar en blanco para mantener)' : '' }}</label>
                                <input wire:model="userForm.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                @error('userForm.password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button wire:click="cancelarEdicionUsuario" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                            <button wire:click="guardarUsuario" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Guardar</button>
                        </div>
                    </div>
                @else
                    <!-- Lista de Usuarios -->
                    <div class="mb-4 flex justify-end">
                        <button wire:click="formCrearUsuario" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                            + Crear Usuario
                        </button>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Email</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse($usuariosSubcontratista as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button wire:click="editarUsuario({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 mr-2">Editar</button>
                                        <button wire:click="eliminarUsuario({{ $user->id }})" class="text-red-600 hover:text-red-900" onclick="confirm('¿Estás seguro de eliminar este usuario?') || event.stopImmediatePropagation()">Eliminar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center dark:text-gray-400">
                                        No hay usuarios registrados para este subcontratista.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-end p-6 border-t border-solid border-gray-200 rounded-b dark:border-gray-700">
                <button wire:click="cerrarModalUsuarios" class="text-gray-500 background-transparent font-bold uppercase px-6 py-2 text-sm outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
