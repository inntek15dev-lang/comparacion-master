<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentoExcepcionCriticidad extends Model
{
    use HasFactory;

    protected $table = 'documento_excepciones_criticidad';

    protected $fillable = [
        'mandante_id',
        'nombre_documento_id',
        'excepcionable_id',
        'excepcionable_type',
        'afecta_cumplimiento_override',
        'restringe_acceso_override',
        'es_perseguidor_override',
        'valido_hasta',
        'justificacion', // <-- NUEVO
        'accion_override', // <-- NUEVO
        'created_by_user_id', // <-- NUEVO
    ];

    protected $casts = [
        'afecta_cumplimiento_override' => 'boolean',
        'restringe_acceso_override' => 'boolean',
        'es_perseguidor_override' => 'boolean',
        'valido_hasta' => 'date',
    ];

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    public function nombreDocumento(): BelongsTo
    {
        return $this->belongsTo(NombreDocumento::class, 'nombre_documento_id');
    }

    public function excepcionable(): MorphTo
    {
        return $this->morphTo();
    }

    // <-- NUEVO -->
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}