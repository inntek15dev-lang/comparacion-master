<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Tarea existente para notificaciones de vencimiento.
        $schedule->command('notificaciones:vencimiento')
                 ->dailyAt('03:00')
                 ->timezone('America/Santiago'); // Recomendación: Añadir zona horaria para consistencia

        // <<< INICIO DE LA MODIFICACIÓN CANÓNICA: EL VIGILANTE NOCTURNO >>>
        
        // Esta nueva línea ordena al sistema ejecutar nuestro comando de supervisión
        // todos los días a las 4:00 AM. Se añade junto a la tarea existente.
        $schedule->command('supervision:calcular-promedios')
                 ->dailyAt('04:00')
                 ->timezone('America/Santiago') // IMPORTANTE: Ajustar a su zona horaria local si es diferente.
                 ->withoutOverlapping();
                 
        // <<< FIN DE LA MODIFICACIÓN CANÓNICA >>>
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}