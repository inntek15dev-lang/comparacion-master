<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarpetaVerificacion extends Model
{
    use HasFactory;

    protected $table = 'carpetas_verificacion';

    protected $fillable = [
        'contratista_unidad_organizacional_id',
        'anio',
        'mes',
        'estado',
        'tipo_envio',
        'fecha_emision_asignada',
        'fecha_envio',
        'fecha_emision',
        // Campos de asignación
        'supervisor_id',
        'analista_id',
        'auditor_id',
        'fecha_asignacion',
        'fecha_inicio_revision',
        'fecha_fin_revision',
        'fecha_auditoria',
        'estado_revision',
        'observaciones_analista',
        'observaciones_auditor',
        // Pre-cierre fields
        'fin_contratados_periodo',
        'fin_desvinculados_periodo',
        'fin_total_vigentes',
        'fin_trabajadores_revisados',
        'fin_remuneraciones_pagadas',
        'fin_cotizaciones_pagadas',
        'fin_aviso_previo_trabajadores',
        'fin_aviso_previo_total',
        'fin_anio_servicio_trabajadores',
        'fin_anio_servicio_total',
        'fin_feriado_trabajadores',
        'fin_feriado_total',
        'fin_liquido_total',
        'fin_doy_finalizado',
        'fin_observaciones_json',
        'ia_datos_extraidos',
    ];

    protected $casts = [
        'fecha_emision_asignada' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_emision' => 'datetime',
        'fecha_asignacion' => 'datetime',
        'fecha_inicio_revision' => 'datetime',
        'fecha_fin_revision' => 'datetime',
        'fecha_auditoria' => 'datetime',
        'fin_doy_finalizado'     => 'boolean',
        'fin_liquido_total'      => 'integer',
        'fin_observaciones_json' => 'array',
        'ia_datos_extraidos'     => 'boolean',
    ];

    public function vinculacion()
    {
        return $this->belongsTo(ContratistaUnidadOrganizacional::class, 'contratista_unidad_organizacional_id');
    }

    public function trabajadoresVerificados()
    {
        return $this->hasMany(CarpetaVerificacionTrabajador::class, 'carpeta_verificacion_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoVerificacion::class, 'carpeta_verificacion_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    // Accessor para compatibilidad con vistas
    public function getNombreMesAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$this->mes] ?? '';
    }

    public function getMotivoDevolucionAttribute(): ?string
    {
        if (empty($this->observaciones_analista)) return null;

        // Buscamos el bloque [DEVOLUCIÓN POR AUDITOR ...]
        if (preg_match('/MOTIVO:\s*(.*?)\s*\n-{10,}/s', $this->observaciones_analista, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Motivo de devolución enviado por el SUPERVISOR al AUDITOR
     */
    public function getMotivoDevolucionAuditorAttribute(): ?string
    {
        if (empty($this->observaciones_auditor)) return null;

        // Buscamos el bloque [DEVOLUCIÓN POR SUPERVISOR/EMISOR ...]
        if (preg_match('/MOTIVO:\s*(.*?)\s*\n-{10,}/s', $this->observaciones_auditor, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function getFolioAttribute(): string
    {
        $idReg = $this->vinculacion->id_registro ?? $this->id;
        $mesAlt = str_pad($this->mes, 2, '0', STR_PAD_LEFT);
        $idCarp = str_pad($this->id, 5, '0', STR_PAD_LEFT);
        return "{$idReg}-{$this->anio}-{$mesAlt}-{$idCarp}";
    }

    public function getHistorialRevisionAttribute()
    {
        return CarpetaVerificacion::with(['analista', 'auditor'])
            ->where('contratista_unidad_organizacional_id', $this->contratista_unidad_organizacional_id)
            ->where('estado_revision', 'REVISADO')
            ->where(function ($query) {
                // Periodos anteriores cronológicamente
                $query->where('anio', '<', $this->anio)
                      ->orWhere(function ($q) {
                          $q->where('anio', $this->anio)
                            ->where('mes', '<', $this->mes);
                      });
            })
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->take(3)
            ->get();
    }

    // Scopes útiles
    public function scopeEnviados($query)
    {
        return $query->where('estado', 'ENVIADO');
    }

    public function scopePendientesAsignar($query)
    {
        return $query->where('estado_revision', 'PENDIENTE_ASIGNAR');
    }

    public function scopeAsignadosA($query, $analistaId)
    {
        return $query->where('analista_id', $analistaId);
    }

    // ------------------------------------
    // Incidencias (obs + contingencias)
    // ------------------------------------

    /**
     * Todas las incidencias (observaciones y contingencias) directamente ligadas
     * a la carpeta — tanto las de empresa como las de trabajadores individuales.
     */
    public function incidencias()
    {
        return $this->hasMany(CarpetaTrabajadorContingencia::class, 'carpeta_verificacion_id');
    }

    /**
     * Todas las Solicitudes Complementarias asociadas a este certificado.
     * Un certificado puede tener MÚLTIPLES SCs enviadas en distintos momentos.
     */
    public function solicitudesComplementarias()
    {
        return $this->hasMany(SolicitudComplementaria::class, 'carpeta_verificacion_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Genera el siguiente código incremental GLOBAL para una incidencia.
     *
     * El código es ÚNICO EN TODO EL SISTEMA (no por carpeta), lo que permite
     * que cualquier operador localice una incidencia con solo el número,
     * sin necesitar RUT, ID de carpeta, contrato ni lugar.
     *
     * Secuencia: 100.001, 100.002, 100.003 ... sin reiniciar por carpeta.
     */
    public function generarCodigoIncidencia(): int
    {
        // MAX global — sin filtro de carpeta_verificacion_id
        $max = CarpetaTrabajadorContingencia::max('codigo');
        return max(100001, ($max ?? 100000) + 1);
    }
}
