@props(['active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="flex items-center justify-between w-full px-4 py-2 text-sm font-normal text-indigo-200 rounded-md hover:bg-indigo-600/50 hover:text-white transition-colors duration-200 group"
            :class="{ 'bg-indigo-600/50 text-white': open || {{ $active ? 'true' : 'false' }} }">
        
        <span class="flex-1 whitespace-nowrap text-left">
            {{ $slot }}
        </span>

        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-90': open }" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
        </svg>
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="py-1 pl-4 space-y-1"
         style="display: none;">
        {{ $content }}
    </div>
</div>
