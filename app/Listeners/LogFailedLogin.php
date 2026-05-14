<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use App\Services\AuditService;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        // Solo registramos si el AuditService está disponible
        // Usamos el AuditService para registrar la alerta de seguridad
        AuditService::log(
            'seguridad-alerta',
            'Intento de inicio de sesión fallido detectado.',
            [
                'email_intentado' => $event->credentials['email'] ?? 'N/A',
                'ip_origen' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'contexto' => 'LOGIN_FAIL'
            ]
        );
    }
}
