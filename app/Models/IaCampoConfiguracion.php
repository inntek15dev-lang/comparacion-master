<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IaCampoConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'ia_campos_configuracion';

    protected $fillable = [
        'regla_documental_id',
        'campo_clave',          // 'fecha_emision' | 'criterio_{criterio_evaluacion_id}'
        'etiqueta',             // Label legible (ej: "Clase de Conducir")
        'tipo_dato',            // texto | fecha | rut | numero
        'es_requerido',         // Si falla match → documento RECHAZADO
        'mapea_a_columna',      // Columna de documentos_cargados donde escribir al confirmar (nullable)
        'descripcion_ia',       // Hint para el prompt (qué buscar en el PDF)
        'valor_esperado',       // Valor que el MATCH DEL SISTEMA compara vs. extraído (solo criterios)
        'criterio_evaluacion_id', // FK trazabilidad origen — solo para campos tipo criterio
        'formato_muestra_id',     // FK formato de muestra (imagen de referencia para multimodal)
        'orden',
        'is_active',
    ];

    protected $casts = [
        'es_requerido' => 'boolean',
        'is_active'    => 'boolean',
        'orden'        => 'integer',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function reglaDocumental(): BelongsTo
    {
        return $this->belongsTo(ReglaDocumental::class, 'regla_documental_id');
    }

    public function criterioEvaluacion(): BelongsTo
    {
        return $this->belongsTo(CriterioEvaluacion::class, 'criterio_evaluacion_id');
    }

    public function formatoMuestra(): BelongsTo
    {
        return $this->belongsTo(FormatoDocumentoMuestra::class, 'formato_muestra_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('is_active', true)->orderBy('orden');
    }

    public function scopeRequeridos($query)
    {
        return $query->where('es_requerido', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Indica si este campo corresponde a un CRITERIO de la regla.
     * Clave: 'criterio_{criterio_evaluacion_id}'
     * La IA SOLO extrae. El MATCH DEL SISTEMA compara contra valor_esperado.
     */
    public function esCriterio(): bool
    {
        return str_starts_with($this->campo_clave, 'criterio_');
    }

    /**
     * Indica si este campo debe escribirse en documentos_cargados al confirmar.
     * Solo aplica a columnas directas (fecha_emision, fecha_vencimiento, periodo).
     */
    public function mapeaADocumento(): bool
    {
        return !is_null($this->mapea_a_columna) &&
               in_array($this->mapea_a_columna, ['fecha_emision', 'fecha_vencimiento', 'periodo']);
    }
}
