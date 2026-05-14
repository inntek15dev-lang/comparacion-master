<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Código del País Activo
    |--------------------------------------------------------------------------
    |
    | Este valor determina el país para la instancia actual de la aplicación.
    | Se lee desde la variable de entorno APP_COUNTRY_CODE.
    | El valor por defecto es 'cl' (Chile) si la variable no está definida.
    |
    */
    'code' => env('APP_COUNTRY_CODE', 'cl'),

    /*
    |--------------------------------------------------------------------------
    | Países Soportados
    |--------------------------------------------------------------------------
    |
    | Una lista de los países soportados por la aplicación.
    | La clave es el código de 2 letras (en minúsculas) y el valor es el
    | nombre completo del país. Esto se usa para el 'alt' text de la bandera
    | y puede ser usado para otras funcionalidades.
    |
    */
    'supported' => [
        'cl' => 'Chile',
        'co' => 'Colombia',
        'ar' => 'Argentina',
        'uy' => 'Uruguay',
        'br' => 'Brasil',
        'pe' => 'Perú',
    ],

];