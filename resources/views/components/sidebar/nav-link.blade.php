@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'flex items-center p-2 text-base font-normal text-white bg-white/20 rounded-lg transition-colors duration-200 group'
            : 'flex items-center p-2 text-base font-normal text-indigo-100 rounded-lg hover:bg-white/10 hover:text-white transition-colors duration-200 group';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if (isset($icon))
        <span class="flex-shrink-0 w-6 h-6 text-indigo-200 group-hover:text-white transition-colors duration-200"
              :class="{'text-white': {{ $active ? 'true' : 'false' }} }">
            {{ $icon }}
        </span>
    @endif
    <span class="ml-3 flex-1 whitespace-nowrap transition-opacity duration-200" x-show="!sidebarCollapsed" x-cloak>
        {{ $slot }}
    </span>
</a>