<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarioVerificacion extends Model
{
    use HasFactory;

    protected $table = 'calendario_verificacion';

    protected $fillable = [
        'mandante_id',
        'anio',
        'mes',
        'fecha_apertura',
        'fecha_cierre',
        'fecha_cierre_fuera_plazo',
        'fecha_emision',
        'fecha_emision_fuera_plazo',
        'is_inicio',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'fecha_cierre_fuera_plazo' => 'date',
        'fecha_emision' => 'date',
        'fecha_emision_fuera_plazo' => 'date',
        'is_inicio' => 'boolean',
    ];

    public function mandante()
    {
        return $this->belongsTo(Mandante::class);
    }

    public function getNombreMesAttribute()
    {
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $nombres[$this->mes] ?? 'Desconocido';
    }

    public function getNombrePeriodoAttribute()
    {
        $fecha = \Carbon\Carbon::create($this->anio, $this->mes, 1)->subMonth();
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $nombres[$fecha->month] . ' ' . $fecha->year;
    }
}
