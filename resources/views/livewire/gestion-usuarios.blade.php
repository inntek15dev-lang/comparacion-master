<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8" style="width: 90%; max-width: 95%;">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                @if (session()->has('message')) <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('message') }}</p></div> @endif
                @if (session()->has('error')) <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div> @endif

                <!-- ============== SECCIÓN DE FILTROS ============== -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <input wire:model.live.debounce.500ms="search" type="text" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" placeholder="Buscar por nombre...">
                    
                    <input wire:model.live.debounce.500ms="filtroEmpresa" type="text" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" placeholder="Buscar por empresa...">
                    
                    <input wire:model.live.debounce.500ms="filtroEmail" type="text" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" placeholder="Buscar por email...">

                    <select wire:model.live="filtroRol" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todos los Roles --</option>
                        @php
                            $roleDisplayMapFilter = [
                                'Mandante_Admin' => 'Principal_Admin',
                                'Mandante_Validator' => 'Principal_Validator',
                                'Mandante_Ver' => 'Principal_Ver',
                                'ASEM_Admin' => 'Oval_Admin',
                                'ASEM_Validator' => 'Oval_Validator',
                            ];
                        @endphp
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $roleDisplayMapFilter[$role->name] ?? $role->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filtroEstado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todos los Estados --</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <!-- ========================================================= -->

                <div class="flex justify-end mb-4">
                    <x-primary-button wire:click="create()">
                        Crear Nuevo Usuario
                    </x-primary-button>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-600 to-indigo-700">
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Nombre</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider" style="min-width: 180px;">Empresa</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Email</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Rol</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $user)
                            @php
                                $bgColor = $index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
                            @endphp
                            <tr wire:key="user-{{ $user->id }}" class="{{ $bgColor }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-150">
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $user->name }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm" style="min-width: 180px; max-width: 250px; word-wrap: break-word; white-space: normal;">
                                    @if($user->user_type === 'asem')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                            ASEM
                                        </span>
                                    @elseif($user->mandante)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $user->mandante->razon_social }}
                                        </span>
                                    @elseif($user->contratista)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            {{ $user->contratista->razon_social }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $user->email }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm">
                                    @php
                                        $roleName = $user->roles->first()->name ?? 'Sin Rol';
                                        // Mapeo de nombres de roles para mostrar (por cambio legal: mandante -> principal, asem -> oval)
                                        $roleDisplayMap = [
                                            'Mandante_Admin' => 'Principal_Admin',
                                            'Mandante_Validator' => 'Principal_Validator',
                                            'Mandante_Ver' => 'Principal_Ver',
                                            'ASEM_Admin' => 'Oval_Admin',
                                            'ASEM_Validator' => 'Oval_Validator',
                                        ];
                                        $displayRoleName = $roleDisplayMap[$roleName] ?? $roleName;
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $displayRoleName }}
                                    </span>
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button wire:click="edit({{ $user->id }})" class="inline-flex items-center px-2.5 py-1.5 border border-blue-300 text-xs font-medium rounded text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                            Editar
                                        </button>
                                        <button wire:click="toggleStatus({{ $user->id }})" class="inline-flex items-center px-2.5 py-1.5 border border-indigo-300 text-xs font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                        <button wire:click="confirmUserDeletion({{ $user->id }})" class="inline-flex items-center px-2.5 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="font-medium">No se encontraron usuarios</span>
                                        <span class="text-sm">Intenta ajustar los filtros de búsqueda</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ================================================================== -->
                <!-- INICIO DE LA CORRECCIÓN: SECCIÓN DE PAGINACIÓN PROFESIONAL         -->
                <!-- ================================================================== -->
                @if ($users->hasPages())
                <div class="mt-6 px-2 py-4 border-t border-gray-200 dark:border-gray-700 sm:flex sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-400">
                        Mostrando <span class="font-medium">{{ $users->firstItem() }}</span> a <span class="font-medium">{{ $users->lastItem() }}</span> de <span class="font-medium">{{ $users->total() }}</span> resultados
                    </div>
                    <div>
                        {{-- Se especifica la vista 'pagination::tailwind' para forzar el estilo correcto --}}
                        {{ $users->links('pagination::tailwind') }}
                    </div>
                </div>
                @endif
                <!-- ================================================================== -->
                <!-- FIN DE LA CORRECCIÓN                                               -->
                <!-- ================================================================== -->

            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Usuario -->
    @if ($isModalOpen)
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">{{ $userId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</h3>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input type="text" wire:model.defer="name" id="name" class="mt-1 form-input block w-full"> @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" wire:model.defer="email" id="email" class="mt-1 form-input block w-full"> @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="selectedRole" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                            <select wire:model.live="selectedRole" id="selectedRole" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un rol...</option>
                                @php
                                    $roleDisplayMapModal = [
                                        'Mandante_Admin' => 'Principal_Admin',
                                        'Mandante_Validator' => 'Principal_Validator',
                                        'Mandante_Ver' => 'Principal_Ver',
                                        'ASEM_Admin' => 'Oval_Admin',
                                        'ASEM_Validator' => 'Oval_Validator',
                                    ];
                                @endphp
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $roleDisplayMapModal[$role->name] ?? $role->name }}</option>
                                @endforeach
                            </select> @error('selectedRole') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @if (str_contains(strtolower($selectedRole ?? ''), 'contratista'))
                        <div class="mb-4 animate-fade-in">
                            <label for="contratista_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asociar a Contratista</label>
                            <select wire:model.live="contratista_id" id="contratista_id" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un contratista...</option>
                                @foreach($contratistas as $contratista)
                                    <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                                @endforeach
                            </select> @error('contratista_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        
                        {{-- Selector de Vinculaciones para Contratista_User - SOLO visible para Contratista_Admin --}}
                        @if ($esContratistaAdmin && $selectedRole === 'Contratista_User' && $contratista_id)
                        <div class="mb-4 animate-fade-in">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Vinculaciones Asignadas
                                <span class="text-xs text-gray-500">(El usuario solo podrá gestionar las entidades de estas vinculaciones)</span>
                            </label>
                            @if(count($vinculacionesDisponibles) > 0)
                            <div class="max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 space-y-2 bg-gray-50 dark:bg-gray-700">
                                @foreach($vinculacionesDisponibles as $vinc)
                                <label class="flex items-start space-x-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           wire:model.defer="vinculacionesSeleccionadas" 
                                           value="{{ $vinc['id'] }}" 
                                           class="mt-0.5 form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $vinc['label'] }}</span>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">Este contratista no tiene vinculaciones activas.</p>
                            @endif
                            @error('vinculacionesSeleccionadas') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        @if (str_contains(strtolower($selectedRole ?? ''), 'mandante'))
                        <div class="mb-4 animate-fade-in">
                            <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asociar a Principal</label>
                            <select wire:model.defer="mandante_id" id="mandante_id" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un principal...</option>
                                @foreach($mandantes as $mandante)
                                    <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                @endforeach
                            </select> @error('mandante_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                            <input type="password" wire:model.defer="password" id="password" class="mt-1 form-input block w-full" placeholder="Dejar en blanco para no cambiar">
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Contraseña</label>
                            <input type="password" wire:model.defer="password_confirmation" id="password_confirmation" class="mt-1 form-input block w-full">
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Guardar</button>
                        <button type="button" wire:click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal de confirmación de borrado -->
    @if ($confirmingUserDeletionId)
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Eliminar Usuario</h3>
                            <div class="mt-2"><p class="text-sm text-gray-500 dark:text-gray-400">¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="deleteUser()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Eliminar</button>
                    <button wire:click="$set('confirmingUserDeletionId', null)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>