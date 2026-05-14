<?php

namespace App\Livewire\Forms;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $trustDevice = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::validate($this->only(['email', 'password']))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $user = User::where('email', $this->email)->first();

        // ============================================================
        // GUARDIA: CONTRATISTA INACTIVO
        // Verificar si el usuario pertenece a una contratista desactivada.
        // ============================================================
        if ($user->contratista_id) {
            $user->load('contratista');
            if ($user->contratista && !$user->contratista->is_active) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'form.email' => 'CONTRATISTA INACTIVO. Comuníquese con la empresa principal.',
                ]);
            }
        }

        // ============================================================
        // 2FA DESACTIVADO TEMPORALMENTE PARA DESARROLLO
        // Descomentar el bloque de abajo para reactivar el 2FA
        // ============================================================
        
        // Login directo sin 2FA (MODO DESARROLLO)
        Auth::login($user, false);
        RateLimiter::clear($this->throttleKey());
        return;

        /* --- 2FA ORIGINAL (COMENTADO) ---
        // Verificar si el dispositivo es de confianza
        if ($this->isDeviceTrusted($user)) {
            Auth::login($user, false); // El parámetro "remember" se establece en 'false'
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Si el dispositivo no es de confianza, enviar código 2FA
        $user->generateTwoFactorCode();
        Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));

        // Se elimina 'login.remember' de la sesión
        session([
            'login.id' => $user->id,
            'login.trust_device' => $this->trustDevice
        ]);

        // Lanzar una excepción especial para que el componente de la vista sepa redirigir
        throw ValidationException::withMessages([
            'form.email' => '2FA_REQUIRED',
        ]);
        --- FIN 2FA ORIGINAL --- */
    }

    /**
     * Check if the current device is trusted for the user.
     */
    protected function isDeviceTrusted(User $user): bool
    {
        $cookieToken = Cookie::get('trusted_device');

        if (! $cookieToken) {
            return false;
        }

        $trustedDevice = $user->trustedDevices()
            ->where('remember_token', hash('sha256', $cookieToken))
            ->first();

        return (bool) $trustedDevice;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}