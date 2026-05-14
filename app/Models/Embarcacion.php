<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\CriticidadDocumentoService;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Embarcacion extends Model
{
    use HasFactory;

    protected $table = 'embarcaciones';
    protected $fillable = [ 'contratista_id', 'matricula_letras', 'matricula_numeros', 'ano_fabricacion', 'tipo_embarcacion_id', 'tenencia_vehiculo_id', 'is_active' ];
    protected $casts = [ 'is_active' => 'boolean' ];

    protected static function booted(): void
    {
        static::deleted(function (Embarcacion $embarcacion) {
            if ($embarcacion->isForceDeleting()) {
                $embarcacion->vinculaciones()->delete();
            }
        });
    }

    public function vinculaciones(): HasMany
    {
        return $this->hasMany(EmbarcacionAsignacion::class, 'embarcacion_id');
    }

    public function contratista(): BelongsTo { return $this->belongsTo(Contratista::class, 'contratista_id'); }
    public function tipoEmbarcacion(): BelongsTo { return $this->belongsTo(TipoEmbarcacion::class, 'tipo_embarcacion_id'); }
    public function tenencia(): BelongsTo { return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id'); }
    public function getMatriculaCompletaAttribute(): string { return strtoupper($this->matricula_letras) . ' - ' . $this->matricula_numeros; }
    
    // ================== INICIO DE LA MODIFICACIÓN ==================
    public function getIdentificadorUnicoAttribute(): string 
    {
        return strtoupper($this->matricula_letras . $this->matricula_numeros);
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public function anulacionManualActiva(): MorphOne { $hoy = Carbon::today(); return $this->morphOne(DocumentoExcepcionCriticidad::class, 'excepcionable')->where('nombre_documento_id', 99999)->whereNotNull('accion_override')->where(function ($query) use ($hoy) { $query->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta'); })->latestOfMany(); }

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