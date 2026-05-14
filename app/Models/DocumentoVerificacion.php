<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoVerificacion extends Model
{
    use HasFactory;

    protected $table = 'documentos_verificacion';

    protected $fillable = [
        'carpeta_verificacion_id',
        'requisito_verificacion_id',
        'path',
        'nombre_original',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    // Accessor para compatibilidad con vistas (usan $doc->ruta_archivo)
    public function getRutaArchivoAttribute(): ?string
    {
        return $this->path;
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) return null;

        // Si está encriptado o es un archivo de verificación (que queremos proteger)
        // Redirigimos al controlador seguro
        return route('documento.seguro.descargar', ['id' => $this->id, 'type' => 'verificacion']);
    }

    public function carpeta()
    {
        return $this->belongsTo(CarpetaVerificacion::class, 'carpeta_verificacion_id');
    }

    public function requisito()
    {
        return $this->belongsTo(RequisitoVerificacion::class, 'requisito_verificacion_id');
    }
}
