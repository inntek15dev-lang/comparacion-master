<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        try {
            $this->form->authenticate();
        } catch (ValidationException $e) {
            // Si la excepción es por 2FA, redirigimos a la página de verificación
            if ($e->validator->errors()->has('form.email') && $e->validator->errors()->first('form.email') === '2FA_REQUIRED') {
                $this->redirect(route('two-factor.login'), navigate: false);
                return;
            }
            // Si es otra excepción de validación, la lanzamos de nuevo
            throw $e;
        }

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false));
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo Electrónico')" class="text-white" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            @error('form.email')
                <div class="mt-2 px-3 py-2 rounded-md bg-red-800/80 text-sm font-bold text-white tracking-wide">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" class="text-white" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            @error('form.password')
                <div class="mt-2 px-3 py-2 rounded-md bg-red-800/80 text-sm font-bold text-white tracking-wide">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <!-- Trust Device -->
        <div class="block mt-4">
            <label for="trustDevice" class="inline-flex items-center">
                <input wire:model="form.trustDevice" id="trustDevice" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="trustDevice">
                <span class="ms-2 text-sm text-white">{{ __('Recordar este dispositivo') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-white hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Iniciar Sesión') }}
            </x-primary-button>
        </div>
    </form>

</div>