<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentoSolicitudComplementaria extends Model
{
    use HasFactory;

    protected $table = 'documentos_solic_complementarias';

    protected $fillable = [
        'solicitud_complementaria_id',
        'requisito_verificacion_id',
        'path',
        'nombre_original',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudComplementaria::class, 'solicitud_complementaria_id');
    }

    public function requisito()
    {
        return $this->belongsTo(RequisitoVerificacion::class, 'requisito_verificacion_id');
    }
}
