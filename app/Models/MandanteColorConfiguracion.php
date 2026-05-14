<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandanteColorConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'mandante_color_configuraciones';

    protected $fillable = [
        'mandante_id',
        'horas_inicio',
        'horas_fin',
        'color_fondo',
        'color_texto',
    ];

    /**
     * Obtiene el mandante al que pertenece esta configuración.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }
}