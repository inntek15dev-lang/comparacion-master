<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoConfiguracionCriticidad extends Model
{
    use HasFactory;

    protected $table = 'documento_configuraciones_criticidad';

    protected $fillable = [
        'mandante_id',
        'nombre_documento_id',
        'afecta_cumplimiento',
        'restringe_acceso',
        'es_perseguidor',
    ];

    protected $casts = [
        'afecta_cumplimiento' => 'boolean',
        'restringe_acceso' => 'boolean',
        'es_perseguidor' => 'boolean',
    ];

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    public function nombreDocumento(): BelongsTo
    {
        return $this->belongsTo(NombreDocumento::class, 'nombre_documento_id');
    }
}