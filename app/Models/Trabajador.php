<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\CriticidadDocumentoService;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Trabajador extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $table = 'trabajadores';
    protected $fillable = [ 'contratista_id', 'nombres', 'apellido_paterno', 'apellido_materno', 'rut', 'fecha_nacimiento', 'sexo_id', 'nacionalidad_id', 'tipo_permanencia_id', 'email', 'celular', 'estado_civil_id', 'nivel_educacional_id', 'etnia_id', 'fecha_ingreso_empresa', 'direccion_calle', 'direccion_numero', 'direccion_departamento', 'comuna_id', 'is_active'];
    protected $casts = [ 'fecha_nacimiento' => 'date', 'fecha_ingreso_empresa' => 'date', 'is_active' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombres', 'apellido_paterno', 'apellido_materno', 'rut', 
                'email', 'celular', 'is_active', 'direccion_calle', 'comuna_id'
            ])
            ->logOnlyDirty()
            ->useLogName('audit')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => "Creó trabajador: " . $this->nombre_completo . " (RUT: " . $this->rut . ")",
                'updated' => "Editó ficha de trabajador: " . $this->nombre_completo . " (RUT: " . $this->rut . ")",
                'deleted' => "Eliminó trabajador: " . $this->nombre_completo,
                default => "Acción sobre trabajador: {$eventName}"
            })
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void { static::deleted(function (Trabajador $trabajador) { if ($trabajador->isForceDeleting()) { $trabajador->vinculaciones()->delete(); } }); }
    public function contratista(): BelongsTo { return $this->belongsTo(Contratista::class); }
    public function sexo(): BelongsTo { return $this->belongsTo(Sexo::class); }
    public function nacionalidad(): BelongsTo { return $this->belongsTo(Nacionalidad::class); }
    public function estadoCivil(): BelongsTo { return $this->belongsTo(EstadoCivil::class); }
    public function nivelEducacional(): BelongsTo { return $this->belongsTo(NivelEducacional::class); }
    public function etnia(): BelongsTo { return $this->belongsTo(Etnia::class); }
    public function comuna(): BelongsTo { return $this->belongsTo(Comuna::class); }
    public function tipoPermanencia(): BelongsTo { return $this->belongsTo(TipoPermanencia::class, 'tipo_permanencia_id'); }
    public function vinculaciones(): HasMany { return $this->hasMany(TrabajadorVinculacion::class, 'trabajador_id'); }
    public function getNombreCompletoAttribute(): string { return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}"); }
    public function getRegionAttribute() { if ($this->comuna) { return $this->comuna->load('region')->region; } return null; }
    public function anulacionManualActiva(): MorphOne { $hoy = Carbon::today(); return $this->morphOne(DocumentoExcepcionCriticidad::class, 'excepcionable')->where('nombre_documento_id', 99999)->whereNotNull('accion_override')->where(function ($query) use ($hoy) { $query->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta'); })->latestOfMany(); }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    public function dependencias(): BelongsToMany
    {
        return $this->belongsToMany(Dependencia::class, 'trabajador_vinculaciones', 'trabajador_id', 'dependencia_id')
                    ->withTimestamps()
                    ->withPivot(['id', 'unidad_organizacional_mandante_id', 'cargo_mandante_id', 'is_active']);
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public function calcularPorcentajeCumplimiento(int $mandanteId, int $unidadOrganizacionalId): int
    {
        return app(CriticidadDocumentoService::class)
            ->calcularPorcentajeCumplimientoParaEntidad($this, $mandanteId, $unidadOrganizacionalId);
    }
    
    public function determinarAccesoHabilitado(int $mandanteId, int $unidadOrganizacionalId): array
    {
        return app(CriticidadDocumentoService::class)->determinarAccesoFinalRecurso($this, $mandanteId, $unidadOrganizacionalId);
    }
}