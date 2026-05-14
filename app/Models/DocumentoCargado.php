<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DocumentoCargado extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'documentos_cargados';

    public function getNombreRecursoAttribute(): string
    {
        $entidad = $this->entidad;
        if (!$entidad) return 'Recurso desconocido';

        if ($entidad instanceof \App\Models\Trabajador) {
            return "Trabajador " . $entidad->nombre_completo;
        }
        if ($entidad instanceof \App\Models\Vehiculo) {
            return "Vehículo " . $entidad->patente_completa;
        }
        if ($entidad instanceof \App\Models\Contratista) {
            return "Empresa " . $entidad->razon_social;
        }
        
        return class_basename($this->entidad_type) . " ID: " . $this->entidad_id;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre_original_archivo', 'estado_validacion', 'periodo'])
            ->useLogName('audit')
            ->setDescriptionForEvent(function(string $eventName) {
                $recurso = $this->nombre_recurso;
                return match($eventName) {
                    'created' => "Subió documento: " . ($this->nombre_original_archivo ?? 'Archivo nuevo') . " para {$recurso}",
                    'deleted' => "Eliminó documento: " . ($this->nombre_original_archivo ?? 'Archivo eliminado') . " de {$recurso}",
                    default => "Acción sobre documento de {$recurso}: {$eventName}"
                };
            })
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'contratista_id', 'mandante_id', 'unidad_organizacional_id',
        // ================== PERSEGUIDOR/VINCULACIÓN ==================
        // NULL = perseguidor (aplica a todas las vinculaciones del trabajador)
        // Valor = documento específico de esa vinculación (no perseguidor)
        'trabajador_vinculacion_id',
        // ============================================================
        'entidad_id', 'entidad_type',
        'regla_documental_id_origen', 'usuario_carga_id', 'ruta_archivo', 'is_encrypted', 'nombre_original_archivo',
        'mime_type', 'tamano_archivo', 'fecha_emision', 'fecha_vencimiento', 'es_vencimiento_modificado',
        'motivo_modificacion_vencimiento', 'ruta_justificativo_modificacion', 'periodo',
        'estado_validacion', 'resultado_validacion', 'asem_validador_id', 'mandante_validador_id',
        'fecha_validacion', 'fecha_validacion_asem', 'fecha_validacion_mandante', 'observacion_interna_asem',
        'observacion_rechazo', 'observacion_validador',
        'motivo_revalidacion', 'reemplaza_a_id', 'es_error_validador',
        'nombre_documento_snapshot', 'tipo_vencimiento_snapshot', 'valida_emision_snapshot',
        'valida_vencimiento_snapshot', 'valida_solo_mandante_snapshot', 'valor_nominal_snapshot', 'criterios_snapshot',
        'observacion_documento_snapshot', 'formato_documento_snapshot', 'documento_relacionado_id_snapshot'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_validacion' => 'datetime',
        'fecha_validacion_asem' => 'datetime',
        'fecha_validacion_mandante' => 'datetime',
        'is_encrypted' => 'boolean',
        'es_vencimiento_modificado' => 'boolean',
        'es_error_validador' => 'boolean',
        'valida_emision_snapshot' => 'boolean',
        'valida_vencimiento_snapshot' => 'boolean',
        'valida_solo_mandante_snapshot' => 'boolean',
        'criterios_snapshot' => 'array',
    ];

    public function contratista(): BelongsTo { return $this->belongsTo(Contratista::class); }
    public function mandante(): BelongsTo { return $this->belongsTo(Mandante::class); }
    public function unidadOrganizacional(): BelongsTo { return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_id'); }
    public function entidad(): MorphTo { return $this->morphTo(); }
    public function reglaDocumental(): BelongsTo { return $this->belongsTo(ReglaDocumental::class, 'regla_documental_id_origen'); }
    public function usuarioCarga(): BelongsTo { return $this->belongsTo(User::class, 'usuario_carga_id'); }
    public function validadorAsem(): BelongsTo { return $this->belongsTo(User::class, 'asem_validador_id'); }
    public function validadorMandante(): BelongsTo { return $this->belongsTo(User::class, 'mandante_validador_id'); }
    public function reemplazadoPor(): BelongsTo { return $this->belongsTo(DocumentoCargado::class, 'reemplaza_a_id'); }
    /** Vinculación específica del trabajador para la que fue cargado este doc (solo docs no-perseguidores). */
    public function trabajadorVinculacion(): BelongsTo { return $this->belongsTo(TrabajadorVinculacion::class, 'trabajador_vinculacion_id'); }

    /**
     * Dato extraído por IA (Módulo IA Acreditación).
     * Relación 1:1. NO interviene en el flujo normal.
     */
    public function datoExtraidoIa(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\DatoExtraidoIa::class, 'documento_cargado_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->ruta_archivo) {
            return null;
        }

        // Archivos encriptados: siempre redirigen al endpoint seguro
        if ($this->is_encrypted) {
            return route('documento.seguro.descargar', $this->id);
        }

        // Archivos legados planos (compatibilidad hacia atrás)
        if (Storage::disk('public')->exists($this->ruta_archivo)) {
            return route('documento.seguro.descargar', $this->id);
        }

        return null;
    }

    public function getJustificativoUrlAttribute(): ?string
    {
        if ($this->ruta_justificativo_modificacion && Storage::disk('public')->exists($this->ruta_justificativo_modificacion)) {
            return Storage::disk('public')->url($this->ruta_justificativo_modificacion);
        }
        return null;
    }

    public function getEstadoVigenciaAttribute(): string
    {
        $estadoBase = '---';

        if ($this->fecha_vencimiento) {
            $estadoBase = $this->fecha_vencimiento->isPast() ? 'Vencido' : 'Vigente';
        } elseif ($this->tipo_vencimiento_snapshot === 'POR PERIODO') {
            // La lógica de vencimiento para 'POR PERIODO' se maneja en el DocumentoRequeridoService
            // para determinar el estado de cumplimiento general. Aquí solo indicamos el tipo.
            $estadoBase = 'Por Periodo';
        } else {
            // Documentos sin fecha de vencimiento y que no son por periodo son indefinidos (siempre vigentes).
            $estadoBase = 'Vigente';
        }

        if ($this->es_vencimiento_modificado) {
            return $estadoBase . '-Modificado';
        }

        return $estadoBase;
    }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    public function getFechaVencimientoFormateadaAttribute(): string
    {
        if (is_null($this->fecha_vencimiento)) {
            // Si la fecha es nula, verificamos si es un documento por periodo.
            if ($this->tipo_vencimiento_snapshot === 'POR PERIODO' && $this->periodo) {
                // Cargamos la relación con la regla para obtener los días de gracia.
                $this->loadMissing('reglaDocumental');
                
                if ($this->reglaDocumental) {
                    $diasGracia = $this->reglaDocumental->dias_gracia_carga ?? 0;
                    
                    // Calculamos la fecha de vencimiento conceptual.
                    // Es el día de gracia del mes siguiente al periodo cargado.
                    $fechaVencimiento = Carbon::createFromFormat('Y-m', $this->periodo)
                        ->addMonth()
                        ->startOfMonth()
                        ->addDays($diasGracia - 1); // -1 porque startOfMonth es el día 1.
                    
                    return $fechaVencimiento->format('d-m-Y');
                }
            }
            // Si no es por periodo o algo falla, devolvemos INDEFINIDO.
            return 'INDEFINIDO';
        }
        
        // Si hay fecha de vencimiento, la formateamos como siempre.
        return $this->fecha_vencimiento->format('d-m-Y');
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public function getHorasEnColaFormateadoAttribute(): string
    {
        if (!$this->created_at || $this->resultado_validacion) {
            return '---';
        }

        $horasAsem = $this->fecha_validacion_asem ? $this->created_at->diffInHours($this->fecha_validacion_asem) : null;
        $horasMandante = $this->fecha_validacion_mandante ? $this->created_at->diffInHours($this->fecha_validacion_mandante) : null;

        if ($horasAsem !== null && $horasMandante !== null) {
            return (int)$horasAsem . "h / " . (int)$horasMandante . "h";
        } elseif ($horasAsem !== null) {
            return (int)$horasAsem . "h";
        } elseif ($horasMandante !== null) {
            return (int)$horasMandante . "h";
        } else {
            return (int)$this->created_at->diffInHours(now()) . 'h';
        }
    }

    public function getHorasEnColaAttribute(): ?int
    {
        if (!$this->created_at || $this->resultado_validacion) {
            return null;
        }

        $horasAsem = $this->fecha_validacion_asem ? $this->created_at->diffInHours($this->fecha_validacion_asem) : null;
        $horasMandante = $this->fecha_validacion_mandante ? $this->created_at->diffInHours($this->fecha_validacion_mandante) : null;

        if ($horasAsem !== null && $horasMandante !== null) {
            return max($horasAsem, $horasMandante);
        } elseif ($horasAsem !== null) {
            return $horasAsem;
        } elseif ($horasMandante !== null) {
            return $horasMandante;
        } else {
            return $this->created_at->diffInHours(now());
        }
    }

    public function getColorClasesParaCola(): string
    {
        if ($this->estado_validacion === 'Pendiente Validación Mandante') {
            return '';
        }

        $horas = $this->horas_en_cola;

        if (is_null($horas) || !$this->mandante_id) {
            return '';
        }

        $configuraciones = Cache::remember('color_config_mandante_' . $this->mandante_id, now()->addMinutes(60), function () {
            return Mandante::find($this->mandante_id)->colorConfiguraciones ?? collect();
        });

        foreach ($configuraciones as $config) {
            if ($horas >= $config->horas_inicio && $horas <= $config->horas_fin) {
                return "{$config->color_fondo} {$config->color_texto}";
            }
        }

        return '';
    }
}