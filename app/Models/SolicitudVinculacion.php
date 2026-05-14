<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudVinculacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_vinculacion';

    protected $fillable = [
        'contratista_id',
        'tipo_solicitud',
        'mandante_id',
        'contratista_padre_id',
        'estado',
        'aprobado_por_user_id',
        'fecha_aprobacion',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
    ];

    /**
     * La empresa contratista que realiza la solicitud.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    /**
     * El mandante al que se desea vincular.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    /**
     * El contratista principal, en caso de ser una solicitud de subcontratista.
     */
    public function contratistaPadre(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_padre_id');
    }

    /**
     * El usuario que aprobó la solicitud.
     */
    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por_user_id');
    }
}