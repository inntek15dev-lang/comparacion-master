@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'block w-full text-left pl-4 px-4 py-2 text-sm font-medium text-white bg-indigo-600/50 rounded-md transition-colors duration-200'
            : 'block w-full text-left pl-4 px-4 py-2 text-sm font-normal text-indigo-200 rounded-md hover:bg-indigo-600/50 hover:text-white transition-colors duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>