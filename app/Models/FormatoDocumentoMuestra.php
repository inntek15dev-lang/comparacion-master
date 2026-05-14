<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute; // Necesario para la sintaxis moderna

class FormatoDocumentoMuestra extends Model
{
    use HasFactory;

    protected $table = 'formatos_documento_muestra';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ruta_archivo',
        'nombre_archivo_original',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ======================================================================================
    // INICIO DE LA CORRECCIÓN CLAVE
    // Ahora, este método genera una URL a nuestra nueva ruta segura, en lugar de al
    // problemático enlace simbólico de /storage.
    // ======================================================================================
    protected function urlArchivo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->ruta_archivo) {
                    // Genera una URL a la ruta que hemos nombrado 'archivo.publico',
                    // pasándole la ruta del archivo como parámetro.
                    return route('archivo.publico', ['filePath' => $this->ruta_archivo]);
                }
                return null;
            }
        );
    }
    // ======================================================================================
    // FIN DE LA CORRECCIÓN
    // ======================================================================================
    
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($formato) {
            if ($formato->ruta_archivo && Storage::disk('public')->exists($formato->ruta_archivo)) {
                Storage::disk('public')->delete($formato->ruta_archivo);
            }
        });
    }
}