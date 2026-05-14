<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\TipoCondicion;

class ContratistaUnidadOrganizacional extends Pivot
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
    public $timestamps = false;

    protected $table = 'contratista_unidad_organizacional';

    protected static function booted()
    {
        static::updated(function ($linkage) {
            $structuralFields = ['unidad_organizacional_mandante_id', 'dependencia_id', 'numero_contrato'];
            $changes = [];
            
            foreach ($structuralFields as $field) {
                if ($linkage->isDirty($field)) {
                    $changes[$field] = $linkage->$field;
                }
            }

            if (!empty($changes)) {
                $oldUo = $linkage->getOriginal('unidad_organizacional_mandante_id');
                $oldDep = $linkage->getOriginal('dependencia_id');
                $oldContrato = $linkage->getOriginal('numero_contrato');
                $contratistaId = $linkage->contratista_id;

                // 1. Cascada a Trabajadores (Utiliza UO, Dep y Contrato)
                $queryTrab = \App\Models\TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $oldUo)
                    ->where('dependencia_id', $oldDep)
                    ->where('numero_contrato', $oldContrato)
                    ->whereHas('trabajador', function($q) use ($contratistaId) {
                        $q->where('contratista_id', $contratistaId);
                    });
                $queryTrab->update($changes);

                // 2. Cascada a Otros Recursos (Vehículos, Maq, Emb)
                // Estos modelos solo dependen de UO y Dep (según estrucutra actual)
                $changesRecursos = array_intersect_key($changes, array_flip(['unidad_organizacional_mandante_id', 'dependencia_id']));
                
                if (!empty($changesRecursos)) {
                    // Vehículos
                    \App\Models\VehiculoAsignacion::where('unidad_organizacional_mandante_id', $oldUo)
                        ->where('dependencia_id', $oldDep)
                        ->whereHas('vehiculo', function($q) use ($contratistaId) {
                            $q->where('contratista_id', $contratistaId);
                        })
                        ->update($changesRecursos);

                    // Maquinaria
                    \App\Models\MaquinariaAsignacion::where('unidad_organizacional_mandante_id', $oldUo)
                        ->where('dependencia_id', $oldDep)
                        ->whereHas('maquinaria', function($q) use ($contratistaId) {
                            $q->where('contratista_id', $contratistaId);
                        })
                        ->update($changesRecursos);

                    // Embarcaciones
                    \App\Models\EmbarcacionAsignacion::where('unidad_organizacional_mandante_id', $oldUo)
                        ->where('dependencia_id', $oldDep)
                        ->whereHas('embarcacion', function($q) use ($contratistaId) {
                            $q->where('contratista_id', $contratistaId);
                        })
                        ->update($changesRecursos);
                }
            }
        });
    }

    protected $fillable = [
        'contratista_id',
        'mandante_id',
        'unidad_organizacional_mandante_id',
        'dependencia_id',
        'numero_contrato',
        'tipo_contrato_id',
        'periodos_habilitados',
        'acredita',
        'verifica',
        'fecha_inicio_servicio',
        'fecha_fin_servicio',
        'fecha_inicio_acredita',
        'fecha_fin_acredita',
        'fecha_inicio_verifica',
        'fecha_fin_verifica',
        'sap',
        'trabajadores_cuota',
        'id_registro'
    ];

    protected $casts = [
        'periodos_habilitados' => 'array',
        'acredita' => 'boolean',
        'verifica' => 'boolean',
        'fecha_inicio_servicio' => 'date',
        'fecha_fin_servicio' => 'date',
        'fecha_inicio_acredita' => 'date',
        'fecha_fin_acredita' => 'date',
        'fecha_inicio_verifica' => 'date',
        'fecha_fin_verifica' => 'date',
        'trabajadores_cuota' => 'integer'
    ];

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    public function unidadOrganizacionalMandante(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    /**
     * Alias para unidadOrganizacionalMandante para compatibilidad con componentes que usan el nombre corto.
     */
    public function unidadOrganizacional(): BelongsTo
    {
        return $this->unidadOrganizacionalMandante();
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class);
    }

    public function trabajadores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // Relación manual basada en coincidencia de claves UO y Dependencia, filtrando por el contratista a través del trabajador
        // Y asegurando que sea el mismo número de contrato
        $contratistaId = $this->contratista_id;

        return $this->hasMany(\App\Models\TrabajadorVinculacion::class , 'unidad_organizacional_mandante_id', 'unidad_organizacional_mandante_id')
            ->where('dependencia_id', $this->dependencia_id)
            ->where('numero_contrato', $this->numero_contrato)
            ->whereHas('trabajador', function ($query) use ($contratistaId) {
            $query->where('contratista_id', $contratistaId);
        })
            ->where('is_active', true);
    }

    public function vinculacionesTrabajadores()
    {
        // Relación manual basada en coincidencia de claves UO y Dependencia, filtrando por el contratista a través del trabajador
        // Y asegurando que sea el mismo número de contrato
        $contratistaId = $this->contratista_id;

        return $this->hasMany(\App\Models\TrabajadorVinculacion::class , 'unidad_organizacional_mandante_id', 'unidad_organizacional_mandante_id')
            ->where('dependencia_id', $this->dependencia_id)
            ->where('numero_contrato', $this->numero_contrato)
            ->whereHas('trabajador', function ($query) use ($contratistaId) {
            $query->where('contratista_id', $contratistaId);
        })
            ->where('is_active', true);
    }

    public function cargosConfigurados()
    {
        return $this->hasMany(ContratistaUoCargo::class, 'contratista_uo_id');
    }

    /**
     * Condiciones de empresa asignadas a esta vinculación (multi-condición).
     */
    public function condiciones(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoCondicion::class,
            'contratista_uo_tipo_condicion',
            'contratista_uo_id',
            'tipo_condicion_id'
        );
    }
}