<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        if (\App\Services\AuditSettingService::isEnabled() && $event->user) {
            activity('audit')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => Request::ip()
                ])
                ->log('Cierre de sesión (Logout)');
        }
    }
}
