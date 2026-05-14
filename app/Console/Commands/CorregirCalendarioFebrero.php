<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalendarioVerificacion;
use Carbon\Carbon;

class CorregirCalendarioFebrero extends Command
{
    protected $signature = 'verificacion:corregir-febrero';
    protected $description = 'Corrige la fecha de apertura de Febrero 2026 a 2026-02-01';

    public function handle()
    {
        $calendario = CalendarioVerificacion::where('anio', 2026)
            ->where('mes', 2)
            ->first();

        if (!$calendario) {
            $this->error('No se encontró el calendario de Febrero 2026');
            return 1;
        }

        $this->info("Fecha actual de apertura: " . $calendario->fecha_apertura->format('Y-m-d'));
        
        $calendario->update([
            'fecha_apertura' => Carbon::create(2026, 2, 1),
        ]);

        $this->info("Nueva fecha de apertura: 2026-02-01");
        $this->info("¡Calendario de Febrero 2026 corregido!");

        return 0;
    }
}
