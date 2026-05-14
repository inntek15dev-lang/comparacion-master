<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    // ==================================================================
    // INICIO DE LA MODIFICACIÓN: RUTA PARA EL DESAFÍO 2FA
    // ==================================================================
    // Se añade la ruta para la página de verificación del código de 8 dígitos.
    // Se coloca en el middleware 'guest' porque el usuario aún no ha completado la autenticación.
    Volt::route('two-factor-challenge', 'pages.auth.two-factor-challenge')
        ->name('two-factor.login');
    // ==================================================================
    // FIN DE LA MODIFICACIÓN
    // ==================================================================

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // ==================================================================
    // INICIO DE LA MODIFICACIÓN CANÓNICA: RUTA DE LOGOUT ROBUSTA
    // ==================================================================
    // Se crea una ruta POST dedicada para el logout. Esta ruta ejecuta
    // la lógica de cierre de sesión y redirige, evitando el conflicto
    // de estado de Livewire. Esta es la solución definitiva.
    Route::post('logout', function () {
        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();
        return redirect('/');
    })->name('logout');
    // ==================================================================
    // FIN DE LA MODIFICACIÓN CANÓNICA
    // ==================================================================
});