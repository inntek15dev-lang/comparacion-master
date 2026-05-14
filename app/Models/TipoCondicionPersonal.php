<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoCondicionPersonal extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tipos_condicion_personal'; // Nombre correcto de la tabla

    protected $fillable = [
        'mandante_id',
        'nombre',
        'descripcion',
        'requires_special_document',
        'is_active',
    ];

    protected $casts = [
        'requires_special_document' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * El Mandante al que pertenece esta condición personal.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * Obtiene las vinculaciones de trabajadores que tienen esta condición personal.
     * Un tipo de condición personal puede estar en muchas vinculaciones de trabajadores.
     */
    public function trabajadorVinculaciones(): HasMany // <--- NUEVA RELACIÓN AÑADIDA
    {
        return $this->hasMany(TrabajadorVinculacion::class, 'tipo_condicion_personal_id');
    }

    // Comentario: La relación ManyToMany 'trabajadores()' que tenías
    // a través de 'trabajador_tipo_condicion_personal' implicaría una tabla pivote
    // para asociar una condición directamente al trabajador (globalmente para ese trabajador).
    // Según el flujo de la imagen (pantalla 4, donde se edita una VINCULACIÓN y se le asigna
    // una "CONDICION DEL TRABAJADOR"), parece que la condición personal se asocia
    // a una VINCULACIÓN específica (trabajador + UO + Cargo) y no globalmente al trabajador.
    // Por eso, la tabla 'trabajador_vinculaciones' ahora tiene 'tipo_condicion_personal_id'.
    // He comentado la relación original 'trabajadores()' y la tabla pivote
    // 'trabajador_tipo_condicion_personal' no la hemos creado en este nuevo flujo.
    // Si se necesitara una condición global del trabajador (independiente de sus vinculaciones),
    // entonces sí se necesitaría esa tabla pivote y la relación original.

    /*
    public function trabajadores(): BelongsToMany
    {
        return $this->belongsToMany(
            Trabajador::class,
            'trabajador_tipo_condicion_personal', // Tabla pivote
            'tipo_condicion_personal_id',         // FK de este modelo en la pivote
            'trabajador_id'                       // FK del modelo relacionado en la pivote
        )->withTimestamps(); // Siempre es bueno agregar withTimestamps a las tablas pivote
    }
    */
}