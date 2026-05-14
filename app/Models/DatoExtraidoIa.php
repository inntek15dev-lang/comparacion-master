<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatoExtraidoIa extends Model
{
    use HasFactory;

    protected $table = 'datos_extraidos_ia';

    protected $fillable = [
        'documento_cargado_id',
        'fuente',
        'proveedor_ia',
        'datos_extraidos',
        'respuesta_cruda_ia',
        'tokens_entrada',
        'tokens_salida',
        'costo_estimado_usd',
        'match_calculado',
        'detalle_match',
        'observacion_match',
        'estado',
        'usuario_confirma_id',
        'fecha_confirmacion',
    ];

    protected $casts = [
        'datos_extraidos'    => 'array',
        'respuesta_cruda_ia' => 'array',
        'detalle_match'      => 'array',
        'fecha_confirmacion' => 'datetime',
        'tokens_entrada'     => 'integer',
        'tokens_salida'      => 'integer',
        'costo_estimado_usd' => 'decimal:6',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function documentoCargado(): BelongsTo
    {
        return $this->belongsTo(DocumentoCargado::class, 'documento_cargado_id');
    }

    public function usuarioConfirma(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_confirma_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePendienteConfirmacion($query)
    {
        return $query->where('estado', 'MATCH_CALCULADO');
    }

    public function scopeExtraidos($query)
    {
        return $query->whereIn('estado', ['EXTRAIDO', 'MATCH_CALCULADO']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Retorna el ícono y color según el match_calculado.
     */
    public function getBadgeMatchAttribute(): array
    {
        return match($this->match_calculado) {
            'APROBADO'        => ['color' => 'green',  'icon' => '✓', 'label' => 'APROBADO'],
            'RECHAZADO'       => ['color' => 'red',    'icon' => '✗', 'label' => 'RECHAZADO'],
            'REVISION_MANUAL' => ['color' => 'yellow', 'icon' => '⚠', 'label' => 'REVISIÓN MANUAL'],
            default           => ['color' => 'gray',   'icon' => '?', 'label' => 'SIN MATCH'],
        };
    }

    /**
     * Genera el texto de observación legible para el operador.
     * Formato:
     *   Revisado por IA (gemini) — 2026-05-13 10:30
     *   ✓ RUT Titular: 12.345.678-9 (coincide)
     *   ✗ Período: extraído=2025-11 | esperado=2025-12
     *   RESULTADO: RECHAZADO
     */
    public function generarTextoObservacion(): string
    {
        $proveedor = $this->proveedor_ia ?? 'IA';
        $fecha     = now()->format('Y-m-d H:i');
        $lines     = ["Revisado por IA ({$proveedor}) — {$fecha}"];

        foreach ($this->detalle_match ?? [] as $item) {
            $icono = $item['ok'] ? '✓' : '✗';
            if ($item['ok']) {
                $lines[] = "{$icono} {$item['campo']}: {$item['extraido']} (coincide)";
            } else {
                $lines[] = "{$icono} {$item['campo']}: extraído={$item['extraido']} | esperado={$item['esperado']}";
            }
        }

        $lines[] = '';
        $lines[] = 'RESULTADO: ' . ($this->match_calculado ?? 'SIN PROCESAR');

        return implode("\n", $lines);
    }
}
