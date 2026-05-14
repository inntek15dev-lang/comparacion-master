<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contratista extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'razon_social', 'nombre_fantasia', 'rut', 'email_empresa', 
                'telefono_empresa', 'is_active', 'rep_legal_nombres', 'rep_legal_rut'
            ])
            ->logOnlyDirty()
            ->useLogName('audit')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => "Registró nuevo contratista: " . $this->razon_social . " (RUT: " . $this->rut . ")",
                'updated' => "Actualizó perfil de contratista: " . $this->razon_social,
                'deleted' => "Eliminó contratista del sistema: " . $this->razon_social,
                default => "Acción sobre contratista: {$eventName}"
            })
            ->dontSubmitEmptyLogs();
    }

    protected $table = 'contratistas';

    // ================== INICIO DE LA CORRECCIÓN ==================
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
    // ================== FIN DE LA CORRECCIÓN ====================

    protected $fillable = [
        'razon_social', 'nombre_fantasia', 'rut', 'direccion_calle', 'direccion_numero', 'comuna_id', 'telefono_empresa',
        'email_empresa', 'tipo_empresa_legal_id', 'rubro_id', 'rango_cantidad_trabajadores_id', 'mutualidad_id', 'admin_user_id',
        'rep_legal_nombres', 'rep_legal_apellido_paterno', 'rep_legal_apellido_materno', 'rep_legal_rut', 'rep_legal_telefono',
        'rep_legal_email', 'tipo_inscripcion', 'is_active', 'estado_plataforma',
    ];

    protected $casts = [ 'is_active' => 'boolean' ];

    public function adminUser(): BelongsTo { return $this->belongsTo(User::class, 'admin_user_id'); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function tipoEmpresaLegal(): BelongsTo { return $this->belongsTo(TipoEmpresaLegal::class, 'tipo_empresa_legal_id'); }
    public function rubro(): BelongsTo { return $this->belongsTo(Rubro::class, 'rubro_id'); }
    public function comuna(): BelongsTo { return $this->belongsTo(Comuna::class, 'comuna_id'); }
    public function getRegionAttribute() { if ($this->comuna) { return $this->comuna->load('region')->region; } return null; }
    public function rangoCantidadTrabajadores(): BelongsTo { return $this->belongsTo(RangoCantidadTrabajadores::class, 'rango_cantidad_trabajadores_id'); }
    public function mutualidad(): BelongsTo { return $this->belongsTo(Mutualidad::class, 'mutualidad_id'); }
    public function vinculaciones(): HasMany { return $this->hasMany(ContratistaUnidadOrganizacional::class, 'contratista_id'); }
    public function tiposEntidadControlable(): BelongsToMany { return $this->belongsToMany(TipoEntidadControlable::class, 'contratista_tipo_entidad_controlable', 'contratista_id', 'tipo_entidad_controlable_id')->withTimestamps(); }
    public function tiposCondicion(): BelongsToMany { return $this->belongsToMany(TipoCondicion::class, 'contratista_tipo_condicion', 'contratista_id', 'tipo_condicion_id')->withTimestamps(); }
    public function unidadesOrganizacionalesMandante(): BelongsToMany { return $this->belongsToMany(UnidadOrganizacionalMandante::class, 'contratista_unidad_organizacional', 'contratista_id', 'unidad_organizacional_mandante_id')->using(ContratistaUnidadOrganizacional::class)->withPivot('id', 'tipo_condicion_id', 'dependencia_id', 'acredita', 'verifica', 'numero_contrato')->with('mandante:id,razon_social'); }
    public function trabajadores(): HasMany { return $this->hasMany(Trabajador::class, 'contratista_id'); }
    public function vehiculos(): HasMany { return $this->hasMany(Vehiculo::class, 'contratista_id'); }
    public function embarcaciones(): HasMany { return $this->hasMany(Embarcacion::class, 'contratista_id'); }
    public function maquinarias(): HasMany { return $this->hasMany(Maquinaria::class, 'contratista_id'); }
    public function solicitudesVinculacion(): HasMany { return $this->hasMany(SolicitudVinculacion::class, 'contratista_id'); }
    public function solicitudesComoPadre(): HasMany { return $this->hasMany(SolicitudVinculacion::class, 'contratista_padre_id'); }
    public function onboarding(): HasOne { return $this->hasOne(OnboardingContratista::class, 'contratista_id'); }
    public function mandantesAprobados(): BelongsToMany { return $this->belongsToMany(Mandante::class, 'solicitudes_vinculacion', 'contratista_id', 'mandante_id')->wherePivot('estado', 'APROBADA')->withTimestamps(); }
    public function subContratistasAprobados(): BelongsToMany { return $this->belongsToMany(Contratista::class, 'solicitudes_vinculacion', 'contratista_padre_id', 'contratista_id')->wherePivot('estado', 'APROBADA'); }
    public function contratistaPadreAprobado(): BelongsToMany { return $this->belongsToMany(Contratista::class, 'solicitudes_vinculacion', 'contratista_id', 'contratista_padre_id')->wherePivot('estado', 'APROBADA')->wherePivot('tipo_solicitud', 'SUBCONTRATISTA'); }

    public function dependencias(): BelongsToMany
    {
        return $this->belongsToMany(Dependencia::class, 'contratista_dependencia', 'contratista_id', 'dependencia_id')->withTimestamps();
    }

    public function anulacionManualActiva(): MorphOne 
    { 
        $hoy = Carbon::today(); 
        return $this->morphOne(DocumentoExcepcionCriticidad::class, 'excepcionable')
            ->where('nombre_documento_id', 99999)
            ->whereNotNull('accion_override')
            ->where(function ($query) use ($hoy) { 
                $query->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta'); 
            })->latestOfMany(); 
    }

    public function calcularPorcentajeCumplimiento(int $mandanteId, int $unidadOrganizacionalId): int
    {
        return app(CriticidadDocumentoService::class)
            ->calcularPorcentajeCumplimientoParaEntidad($this, $mandanteId, $unidadOrganizacionalId);
    }

    public function determinarAccesoHabilitado(int $mandanteId, int $unidadOrganizacionalId): array
    {
        return app(CriticidadDocumentoService::class)->determinarAccesoFinalRecurso($this, $mandanteId, $unidadOrganizacionalId);
    }

    public function tieneAccesoOval(): bool
    {
        return $this->mandantesAprobados()
            ->where('tiene_oval', true)
            ->whereNotNull('oval_cod')
            ->exists();
    }
}