@props(['active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="flex items-center justify-between w-full p-2 text-base font-normal text-indigo-100 rounded-lg hover:bg-white/10 hover:text-white transition-colors duration-200 group"
            :class="{ 'bg-white/20 text-white': open || {{ $active ? 'true' : 'false' }} }">
        
        <div class="flex items-center">
            @if (isset($icon))
                <span class="flex-shrink-0 w-6 h-6 text-indigo-200 group-hover:text-white transition-colors duration-200"
                      :class="{'text-white': open || {{ $active ? 'true' : 'false' }} }">
                    {{ $icon }}
                </span>
            @endif
            <span class="ml-3 flex-1 whitespace-nowrap text-left transition-opacity duration-200" x-show="!sidebarCollapsed" x-cloak>
                {{ $slot }}
            </span>
        </div>

        <svg x-show="!sidebarCollapsed" x-cloak class="w-6 h-6 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>

    <div x-show="open && !sidebarCollapsed" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="py-2 pl-4 pr-2 space-y-1"
         style="display: none;">
        {{ $content }}
    </div>
</div>