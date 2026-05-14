<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopupVisualizacion extends Model
{
    protected $table = 'popup_visualizaciones';

    protected $fillable = [
        'popup_id',
        'user_id',
        'veces_mostrado',
        'acepto_condiciones',
        'ultima_visualizacion',
    ];

    protected $casts = [
        'acepto_condiciones' => 'boolean',
        'veces_mostrado' => 'integer',
        'ultima_visualizacion' => 'datetime',
    ];

    /**
     * Relación con el popup.
     */
    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }

    /**
     * Relación con el usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Incrementa el contador de visualizaciones.
     */
    public function incrementarVisualizacion(): void
    {
        $this->veces_mostrado++;
        $this->ultima_visualizacion = now();
        $this->save();
    }

    /**
     * Marca como aceptadas las condiciones.
     */
    public function aceptarCondiciones(): void
    {
        $this->acepto_condiciones = true;
        $this->ultima_visualizacion = now();
        $this->save();
    }
}
