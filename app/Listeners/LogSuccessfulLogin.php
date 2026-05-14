<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        if (\App\Services\AuditSettingService::isEnabled()) {
            activity('audit')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => Request::ip()
                ])
                ->log('Inicio de sesión (Login)');
        }
    }
}
