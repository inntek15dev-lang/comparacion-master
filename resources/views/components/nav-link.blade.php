@props(['active' => false, 'theme' => 'light'])

@php
// Se mantiene el nombre del tema 'blue' por simplicidad de la API, pero las clases se ajustan para el fondo indigo.
if ($theme === 'blue') { 
    $classes = ($active ?? false)
                ? 'inline-flex items-center px-1 pt-1 border-b-2 border-white text-white text-sm font-medium leading-5 focus:outline-none focus:border-indigo-300 transition duration-150 ease-in-out'
                : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-indigo-200 text-sm font-medium leading-5 hover:text-white hover:border-indigo-300 focus:outline-none focus:text-white focus:border-indigo-300 transition duration-150 ease-in-out';
} else {
    // Tema por defecto ('light' - el original)
    $classes = ($active ?? false)
                ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 dark:border-indigo-600 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-indigo-700'
                : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-300 focus:border-gray-300 dark:focus:border-gray-700 transition duration-150 ease-in-out';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>