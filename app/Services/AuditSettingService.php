<?php

namespace App\Services;

use App\Models\SistemaConfiguracion;

class AuditSettingService
{
    public static function get($key, $default = null)
    {
        $config = SistemaConfiguracion::where('clave', "audit_{$key}")->first();
        return $config ? $config->valor : $default;
    }

    public static function set($key, $value)
    {
        return SistemaConfiguracion::updateOrCreate(
            ['clave' => "audit_{$key}"],
            ['valor' => $value]
        );
    }

    public static function getEmails()
    {
        $emails = self::get('emails', '');
        return array_filter(array_map('trim', explode(',', $emails)));
    }

    public static function getExportTime()
    {
        return self::get('export_time', '23:58');
    }

    public static function isEnabled()
    {
        return (bool) self::get('enabled', config('audit.enabled'));
    }
}
