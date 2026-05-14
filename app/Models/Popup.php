<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Popup extends Model
{
    protected $table = 'popups';

    protected $fillable = [
        'titulo',
        'contenido',
        'archivo_contenido',
        'roles_destino',
        'max_visualizaciones',
        'requiere_aceptacion',
        'texto_aceptacion',
        'tipo_interaccion',
        'url_destino',
        'fecha_inicio',
        'fecha_fin',
        'is_active',
        'created_by',
        'mandante_id',
    ];

    protected $casts = [
        'roles_destino' => 'array',
        'requiere_aceptacion' => 'boolean',
        'is_active' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'max_visualizaciones' => 'integer',
    ];

    /**
     * Relación con el usuario creador.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el mandante (si aplica).
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    /**
     * Relación con las visualizaciones.
     */
    public function visualizaciones(): HasMany
    {
        return $this->hasMany(PopupVisualizacion::class);
    }

    /**
     * Verifica si el popup está dentro del rango de fechas vigente.
     */
    public function estaVigente(): bool
    {
        $hoy = Carbon::today();
        
        if ($hoy->lt($this->fecha_inicio)) {
            return false;
        }
        
        if ($this->fecha_fin && $hoy->gt($this->fecha_fin)) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtiene el estado de vigencia como texto.
     */
    public function getEstadoVigenciaAttribute(): string
    {
        $hoy = Carbon::today();
        
        if ($hoy->lt($this->fecha_inicio)) {
            return 'Programado';
        }
        
        if ($this->fecha_fin && $hoy->gt($this->fecha_fin)) {
            return 'Expirado';
        }
        
        return 'Vigente';
    }

    /**
     * Verifica si el popup es visible para un usuario específico.
     * 
     * IMPORTANTE: Para popups que requieren aceptación, se muestra SIEMPRE
     * hasta que el usuario acepte, ignorando el límite de visualizaciones.
     */
    public function esVisiblePara(User $user): bool
    {
        // Verificar si está activo
        if (!$this->is_active) {
            return false;
        }

        // Verificar vigencia de fechas
        if (!$this->estaVigente()) {
            return false;
        }

        // Validación de Mandante Específico
        if ($this->mandante_id) {
            if ($user->isMandante() && $user->mandante_id !== $this->mandante_id) {
                return false; // Otro mandante no puede ver popups ajenos
            }
            if ($user->isContratista()) {
                // Verificar si el contratista del usuario está vinculado a este mandante
                $vinculado = \DB::table('solicitudes_vinculacion')
                    ->where('contratista_id', $user->contratista_id)
                    ->where('mandante_id', $this->mandante_id)
                    ->where('estado', 'APROBADA')
                    ->exists();
                if (!$vinculado) {
                    return false;
                }
            }
            // Si es ASEM y no es admin, no mostrar por defecto popups de mandante
            if ($user->isAsem() && !$user->hasRole('ASEM_Admin')) {
                return false;
            }
        }

        // Verificar rol del usuario
        $rolesUsuario = $user->getRoleNames()->toArray();
        $rolesPermitidos = $this->roles_destino ?? [];
        
        if (empty(array_intersect($rolesUsuario, $rolesPermitidos))) {
            return false;
        }

        // Obtener registro de visualización del usuario
        $visualizacion = $this->visualizaciones()
            ->where('user_id', $user->id)
            ->first();

        // PRIORIDAD 1: Si requiere aceptación, verificar si ya aceptó
        // Si requiere aceptación y NO ha aceptado, SIEMPRE mostrar (bloqueo obligatorio)
        if ($this->requiere_aceptacion) {
            // Si no ha aceptado, mostrar popup (ignorar límite de visualizaciones)
            if (!$visualizacion || !$visualizacion->acepto_condiciones) {
                return true;
            }
            // Si ya aceptó, no mostrar más
            return false;
        }

        // PRIORIDAD 2: Para popups SIN aceptación obligatoria, aplicar límite de visualizaciones
        if ($this->max_visualizaciones > 0) {
            if ($visualizacion && $visualizacion->veces_mostrado >= $this->max_visualizaciones) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope para obtener popups activos y vigentes.
     */
    public function scopeVigentes($query)
    {
        $hoy = Carbon::today();
        
        return $query->where('is_active', true)
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')
                  ->orWhere('fecha_fin', '>=', $hoy);
            });
    }

    /**
     * Obtiene el contenido a mostrar (archivo o texto).
     */
    public function getContenidoMostrarAttribute(): string
    {
        if ($this->archivo_contenido && \Storage::disk('public')->exists($this->archivo_contenido)) {
            return \Storage::disk('public')->get($this->archivo_contenido);
        }
        
        return $this->contenido ?? '';
    }
}
