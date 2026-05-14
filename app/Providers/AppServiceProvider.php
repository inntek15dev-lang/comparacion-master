<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El View::composer ha sido eliminado por completo.
        // La lógica ahora reside directamente en el componente de navegación.
        // El decreto de registro manual de Livewire ha sido purgado para restaurar el autodescubrimiento canónico.

        // Registro de Listeners para Auditoría
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\LogSuccessfulLogin::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            \App\Listeners\LogSuccessfulLogout::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            \App\Listeners\LogFailedLogin::class
        );

        // Registro de cambios de roles (Spatie)
        \Illuminate\Support\Facades\Event::listen(
            \Spatie\Permission\Events\RoleAssigned::class,
            [\App\Listeners\LogRoleChanges::class, 'handleRoleAssigned']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Spatie\Permission\Events\RoleRemoved::class,
            [\App\Listeners\LogRoleChanges::class, 'handleRoleRemoved']
        );

        // Registro de Observer de Seguridad para Borrado Masivo
        \App\Models\Contratista::observe(\App\Observers\SecurityMassDeletionObserver::class);
        \App\Models\Trabajador::observe(\App\Observers\SecurityMassDeletionObserver::class);
        \App\Models\Vehiculo::observe(\App\Observers\SecurityMassDeletionObserver::class);
        \App\Models\DocumentoCargado::observe(\App\Observers\SecurityMassDeletionObserver::class);
        \App\Models\ReglaDocumental::observe(\App\Observers\SecurityMassDeletionObserver::class);

        // Control Global de Auditoría (Prevenir guardado si está desactivado e inyectar IP/Ruta)
        \Spatie\Activitylog\Models\Activity::saving(function ($activity) {
            if (!\App\Services\AuditSettingService::isEnabled()) {
                return false; // Cancela el guardado
            }

            // Inyectar datos de red globales si no existen
            if (request()) {
                $properties = $activity->properties ? $activity->properties->toArray() : [];
                
                if (!isset($properties['ip'])) {
                    $properties['ip'] = request()->ip();
                }
                
                if (!isset($properties['route'])) {
                    $properties['route'] = \Illuminate\Support\Facades\Route::currentRouteName() ?? 'N/A';
                }

                $activity->properties = collect($properties);
            }
        });
    }
}