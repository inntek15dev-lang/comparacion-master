<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadOrganizacionalMandante extends Model
{
    use HasFactory;

    protected $table = 'unidades_organizacionales_mandante';

    protected $fillable = [
        'mandante_id',
        'nombre_unidad',
        'codigo_unidad',
        'descripcion',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['nombre_jerarquico'];

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(UnidadOrganizacionalMandante::class, 'parent_id');
    }

    public function trabajadorVinculaciones(): HasMany
    {
        return $this->hasMany(TrabajadorVinculacion::class, 'unidad_organizacional_mandante_id');
    }

    public function contratistasHabilitados(): BelongsToMany
    {
        return $this->belongsToMany(
            Contratista::class,
            'contratista_unidad_organizacional',
            'unidad_organizacional_mandante_id',
            'contratista_id'
        )
        ->using(ContratistaUnidadOrganizacional::class)
        ->withPivot('id', 'tipo_condicion_id');
    }

    public function vehiculosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(
                Vehiculo::class,
                'vehiculo_asignaciones',
                'unidad_organizacional_mandante_id',
                'vehiculo_id'
            )
            ->using(VehiculoAsignacion::class)
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }

    public function maquinariasAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(
                Maquinaria::class,
                'maquinaria_asignaciones',
                'unidad_organizacional_mandante_id',
                'maquinaria_id'
            )
            ->using(MaquinariaAsignacion::class)
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }

    public function embarcacionesAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(
                Embarcacion::class,
                'embarcacion_asignaciones',
                'unidad_organizacional_mandante_id',
                'embarcacion_id'
            )
            ->using(EmbarcacionAsignacion::class)
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }

    public function todosLosTrabajadoresEnJerarquiaViaVinculaciones(): \Illuminate\Support\Collection
    {
        $trabajadores = collect();

        if (!$this->relationLoaded('trabajadorVinculaciones')) {
            $this->load('trabajadorVinculaciones.trabajador');
        }
        
        $trabajadores = $this->trabajadorVinculaciones->map(function ($vinculacion) {
            return $vinculacion->trabajador;
        })->filter()->unique('id')->values();


        $childrenUOs = $this->children()->with('trabajadorVinculaciones.trabajador')->get();

        foreach ($childrenUOs as $child) {
            $trabajadores = $trabajadores->merge($child->todosLosTrabajadoresEnJerarquiaViaVinculaciones());
        }

        return $trabajadores->unique('id')->values();
    }

    public function reglasDocumentales(): BelongsToMany
    {
        return $this->belongsToMany(
            ReglaDocumental::class,
            'regla_documental_unidad_organizacional',
            'unidad_organizacional_mandante_id',
            'regla_documental_id'
        );
    }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    /**
     * Genera un nombre jerárquico para la unidad organizacional.
     * Ejemplo: "Gerencia General < Subgerencia de Operaciones < Área de Mantenimiento"
     *
     * @return string
     */
    public function getNombreJerarquicoAttribute(): string
    {
        $path = $this->nombre_unidad ?? '';
        
        $parent = $this->parent;
        
        while ($parent) {
            $parentName = $parent->nombre_unidad ?? '[Nombre Inválido]';
            // Se cambia el separador de ' > ' a ' < '
            $path = $parentName . ' < ' . $path;
            $parent = $parent->parent;
        }
        
        return $path;
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public static function getDescendantIdsAndSelf(array $parentUoIds): array
    {
        if (empty($parentUoIds)) {
            return [];
        }

        $allIds = $parentUoIds;
        $currentIds = $parentUoIds;

        while (!empty($currentIds)) {
            $childrenIds = self::whereIn('parent_id', $currentIds)->pluck('id')->all();
            if (empty($childrenIds)) {
                break;
            }
            $allIds = array_merge($allIds, $childrenIds);
            $currentIds = $childrenIds;
        }

        return array_unique($allIds);
    }
}