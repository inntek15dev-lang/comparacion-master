<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextoRechazo extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'textos_rechazo';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'titulo',
        'descripcion_detalle',
        'is_active',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // En el futuro, si un TextoRechazo puede ser parte de muchas "Reglas de Validación Específicas"
    // podrías tener una relación aquí. Por ahora, es un catálogo simple.
    // Ejemplo:
    // public function reglasDeValidacion()
    // {
    //    return $this->hasMany(ReglaValidacionEspecifica::class, 'texto_rechazo_predeterminado_id');
    // }
}