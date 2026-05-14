<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Muestra la página de inicio.
     */
    public function home(): View
    {
        // <<< INICIO DE LA MODIFICACIÓN CANÓNICA >>>
        // La lógica para obtener mandantes ha sido movida al componente Livewire 'InscripcionModal'.
        // El controlador ahora solo devuelve la vista.
        return view('welcome');
        // <<< FIN DE LA MODIFICACIÓN CANÓNICA >>>
    }

    /**
     * Muestra la página "Quiénes Somos".
     */
    public function about(): View
    {
        return view('pages.about');
    }
}