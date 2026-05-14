<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// --- Importaciones de Modelos para Relaciones ---
use App\Models\ReglaDocumental; // Para la relación inversa a ReglaDocumental
use App\Models\CriterioEvaluacion;
use App\Models\SubCriterio;
use App\Models\TextoRechazo;
use App\Models\AclaracionCriterio;

class ReglaDocumentalCriterio extends Model
{
    use HasFactory;

    protected $table = 'regla_documental_criterios';

    protected $fillable = [
        'regla_documental_id',
        // <<< NUEVO >>> El nuevo campo para distinguir la fuente.
        'fuente_validacion',
        'criterio_evaluacion_id',
        'sub_criterio_id',
        'texto_rechazo_id',
        'aclaracion_criterio_id',
        'orden',
    ];

    /**
     * Los atributos que deberían ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        // No hay casts especiales aquí por ahora, más allá de los timestamps implícitos.
    ];

    // --- Relaciones BelongsTo ---

    /**
     * Obtiene la regla documental a la que pertenece este criterio.
     */
    public function reglaDocumental(): BelongsTo
    {
        return $this->belongsTo(ReglaDocumental::class, 'regla_documental_id');
    }

    /**
     * Obtiene el criterio de evaluación asociado.
     */
    public function criterioEvaluacion(): BelongsTo
    {
        return $this->belongsTo(CriterioEvaluacion::class, 'criterio_evaluacion_id');
    }

    /**
     * Obtiene el sub-criterio asociado (opcional).
     * @deprecated Usar subCriterios() para soporte múltiple.
     */
    public function subCriterio(): BelongsTo
    {
        return $this->belongsTo(SubCriterio::class, 'sub_criterio_id');
    }

    /**
     * Obtiene los sub-criterios asociados (Soporte Múltiple).
     * Incluye los datos condicionales de la pivot para el filtrado dinámico por condición
     * de trabajador (tipo_condicion_personal_id) y empresa (tipo_condicion_id).
     *
     * Regla: NULL en ambas columnas = sub-criterio UNIVERSAL (siempre incluido).
     */
    public function subCriterios(): BelongsToMany
    {
        return $this->belongsToMany(SubCriterio::class, 'regla_criterio_sub_criterio', 'regla_documental_criterio_id', 'sub_criterio_id')
                    ->withPivot('id', 'tipo_condicion_personal_id', 'tipo_condicion_id')
                    ->withTimestamps();
    }

    /**
     * Obtiene el texto de rechazo asociado (opcional).
     */
    public function textoRechazo(): BelongsTo
    {
        return $this->belongsTo(TextoRechazo::class, 'texto_rechazo_id');
    }

    /**
     * Obtiene la aclaración del criterio asociada (opcional).
     */
    public function aclaracionCriterio(): BelongsTo
    {
        return $this->belongsTo(AclaracionCriterio::class, 'aclaracion_criterio_id');
    }
}