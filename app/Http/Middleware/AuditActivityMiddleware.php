<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AuditActivityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (\App\Services\AuditSettingService::isEnabled() && Auth::check()) {
            // Logueamos solo peticiones GET (navegación) que no sean Livewire interno o AJAX
            if ($request->isMethod('GET') && !$request->ajax() && !$request->hasHeader('X-Livewire')) {
                
                $routeName = $request->route() ? $request->route()->getName() : 'unknown';
                $description = "Acceso al módulo: " . strtoupper($routeName);

                // Caso especial: Visualización de archivos individuales
                if ($routeName === 'archivo.publico') {
                    $filePath = $request->route('filePath');
                    $fileName = basename($filePath);
                    $isDownload = $request->has('download') ? 'DESCARGÓ' : 'VIO';
                    $description = "{$isDownload} el archivo: {$fileName}";
                }
                
                activity('audit')
                    ->causedBy(Auth::user())
                    ->log($description);
            }
        }

        return $response;
    }
}
