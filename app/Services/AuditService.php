<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log($action, $description = '', $properties = [])
    {
        if (\App\Services\AuditSettingService::isEnabled()) {
            $activity = activity('audit');
            
            if (Auth::check()) {
                $activity->causedBy(Auth::user());
            }

            $activity->withProperties($properties)
                ->log("[$action] $description");
        }
    }

    /**
     * Registro rápido de alertas de seguridad críticas.
     */
    public static function securityAlert($description, $context, $properties = [])
    {
        $properties = array_merge($properties, ['contexto' => $context, 'ip_origen' => request()->ip()]);
        self::log('seguridad-alerta', $description, $properties);
    }
}
