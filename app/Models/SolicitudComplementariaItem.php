<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudComplementariaItem extends Model
{
    protected $table = 'solicitud_complementaria_items';

    protected $fillable = [
        'solicitud_complementaria_id',
        'carpeta_trabajador_contingencia_id',
        'estado_auditor',
        'monto_solucionado',
        'observaciones_auditor',
    ];

    /**
     * La solicitud complementaria a la que pertenece este ítem.
     */
    public function solicitud()
    {
        return $this->belongsTo(SolicitudComplementaria::class, 'solicitud_complementaria_id');
    }

    /**
     * La incidencia (contingencia u observación) que representa este ítem.
     */
    public function contingencia()
    {
        return $this->belongsTo(CarpetaTrabajadorContingencia::class, 'carpeta_trabajador_contingencia_id');
    }
}
