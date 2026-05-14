<?php

namespace App\Livewire\Pages\Auth;

use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class TwoFactorChallenge extends Component
{
    public ?string $code = '';

    public function mount()
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }
    }

    public function verifyCode()
    {
        $this->validate([
            'code' => ['required', 'string', 'digits:8'],
        ]);

        $userId = session()->get('login.id');
        $user = User::find($userId);

        if (! $user || ! $user->two_factor_code || $user->two_factor_expires_at->isPast()) {
            $this->addError('code', 'El código de verificación es inválido o ha expirado.');
            return;
        }

        if ($this->code !== $user->two_factor_code) {
            $this->addError('code', 'El código de verificación proporcionado es incorrecto.');
            return;
        }

        // Limpiar código
        $user->resetTwoFactorCode();

        // Iniciar sesión (el parámetro "remember" se establece en 'false')
        Auth::login($user, false);

        // Confiar en este dispositivo SÓLO si se marcó la casilla "trust_device"
        if (session()->get('login.trust_device', false)) {
            $this->trustDevice($user);
        }

        // Se elimina 'login.remember' de la sesión
        session()->forget(['login.id', 'login.trust_device']);

        return $this->redirectIntended(default: route('dashboard', absolute: false));
    }

    protected function trustDevice(User $user)
    {
        $token = Str::random(60);

        $user->trustedDevices()->create([
            'remember_token' => hash('sha256', $token),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Cookie::queue('trusted_device', $token, 60 * 24 * 30); // Cookie para 30 días
    }

    public function render()
    {
        return view('livewire.pages.auth.two-factor-challenge');
    }
}