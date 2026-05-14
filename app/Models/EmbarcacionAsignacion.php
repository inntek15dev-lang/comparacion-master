<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmbarcacionAsignacion extends Pivot
{
    protected $table = 'embarcacion_asignaciones';

    public $timestamps = true;

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function embarcacion(): BelongsTo
    {
        return $this->belongsTo(Embarcacion::class, 'embarcacion_id');
    }

    public function unidadOrganizacionalMandante(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    /**
     * Define la relación con el Lugar de Trabajo (Dependencia).
     */
    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id');
    }
    // ================== FIN DE LA MODIFICACIÓN ====================
}