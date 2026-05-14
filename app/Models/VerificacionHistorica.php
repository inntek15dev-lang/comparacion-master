<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificacionHistorica extends Model
{
    use HasFactory;

    protected $table = 'verificaciones_historicas';

    /**
     * Valores válidos de RESULTADO.
     * - Limpio      → sin observaciones ni retención
     * - Obs         → con observaciones documentales
     * - Contingencia → con retención económica
     * - Ambos        → observaciones + retención
     */
    const RESULTADO_LIMPIO       = 'Limpio';
    const RESULTADO_OBS          = 'Obs';
    const RESULTADO_CONTINGENCIA = 'Contingencia';
    const RESULTADO_AMBOS        = 'Ambos';

    const RESULTADOS = [
        self::RESULTADO_LIMPIO,
        self::RESULTADO_OBS,
        self::RESULTADO_CONTINGENCIA,
        self::RESULTADO_AMBOS,
    ];

    const RESULTADOS_LABELS = [
        self::RESULTADO_LIMPIO       => '✅ Limpio',
        self::RESULTADO_OBS          => '⚠️ Con Observaciones',
        self::RESULTADO_CONTINGENCIA => '🔴 Contingencia (Retención)',
        self::RESULTADO_AMBOS        => '🔴⚠️ Obs + Contingencia',
    ];

    protected $fillable = [
        'id_registro',
        'mandante_id',
        'lugar',
        'contrato',
        'periodo_anio',
        'periodo_mes',
        'resultado',
        'monto_retenible',
        'monto_no_retenible',
        'importado_por',
    ];

    protected $casts = [
        'periodo_anio'       => 'integer',
        'periodo_mes'        => 'integer',
        'monto_retenible'    => 'integer',
        'monto_no_retenible' => 'integer',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    public function importadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Período formateado: "Enero 2024"
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($meses[$this->periodo_mes] ?? '?') . ' ' . $this->periodo_anio;
    }

    /**
     * Label legible del resultado
     */
    public function getResultadoLabelAttribute(): string
    {
        return self::RESULTADOS_LABELS[$this->resultado] ?? $this->resultado;
    }

    /**
     * ¿Tiene retención económica?
     */
    public function getTieneRetencionAttribute(): bool
    {
        return in_array($this->resultado, [self::RESULTADO_CONTINGENCIA, self::RESULTADO_AMBOS]);
    }

    /**
     * ¿Tiene observaciones documentales?
     */
    public function getTieneObsAttribute(): bool
    {
        return in_array($this->resultado, [self::RESULTADO_OBS, self::RESULTADO_AMBOS]);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeDelPeriodo($query, int $anio, int $mes)
    {
        return $query->where('periodo_anio', $anio)->where('periodo_mes', $mes);
    }

    public function scopeDelIdRegistro($query, string $idRegistro)
    {
        return $query->where('id_registro', $idRegistro);
    }

    public function scopeDelMandante($query, int $mandanteId)
    {
        return $query->where('mandante_id', $mandanteId);
    }

    public function scopeConRetencion($query)
    {
        return $query->whereIn('resultado', [self::RESULTADO_CONTINGENCIA, self::RESULTADO_AMBOS]);
    }

    public function scopeLimpios($query)
    {
        return $query->where('resultado', self::RESULTADO_LIMPIO);
    }
}
