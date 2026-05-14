<?php

namespace App\Services;

use App\Models\Trabajador;
use App\Models\Mandante;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacturacionService
{
    public function calcularFacturacionPorPeriodo(?int $mandanteId, string $fechaDesde, string $fechaHasta, ?int $contratistaId = null): array
    {
        $fechaDesdeCarbon = Carbon::parse($fechaDesde)->startOfDay();
        $fechaHastaCarbon = Carbon::parse($fechaHasta)->endOfDay();

        $solicitudesQuery = DB::table('solicitudes_vinculacion as sv')
            ->join('contratistas as c', 'sv.contratista_id', '=', 'c.id')
            ->join('mandantes as m', 'sv.mandante_id', '=', 'm.id')
            ->where('sv.estado', 'APROBADA')
            ->select('sv.contratista_id', 'c.rut as rut_contratista', 'c.razon_social', 'sv.mandante_id', 'm.razon_social as mandante_nombre');

        if ($mandanteId) {
            $solicitudesQuery->where('sv.mandante_id', $mandanteId);
        }
        if ($contratistaId) {
            $solicitudesQuery->where('sv.contratista_id', $contratistaId);
        }

        $vinculacionesAprobadas = $solicitudesQuery->get()->keyBy(function ($item) {
            return $item->contratista_id . '-' . $item->mandante_id;
        });

        if ($vinculacionesAprobadas->isEmpty()) {
            return ['resumen' => collect(), 'detalle' => collect(), 'total_general' => 0];
        }

        $trabajadoresQuery = Trabajador::withTrashed()
            ->whereIn('contratista_id', $vinculacionesAprobadas->pluck('contratista_id')->unique())
            ->where('created_at', '<=', $fechaHastaCarbon)
            ->where(function ($query) use ($fechaDesdeCarbon) {
                $query->whereNull('deleted_at')
                      ->orWhere('deleted_at', '>=', $fechaDesdeCarbon);
            });

        // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
        // Si se especifica un contratistaId (contexto Contratista), se añade a la consulta de trabajadores.
        if ($contratistaId) {
            $trabajadoresQuery->where('contratista_id', $contratistaId);
        }
        // ================== FIN DE LA MODIFICACIÓN CANÓNICA ====================

        $trabajadoresFacturables = $trabajadoresQuery
            ->select('id', 'contratista_id', 'rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'created_at', 'deleted_at')
            ->get();

        $resumen = collect();
        $detalle = collect();

        foreach ($vinculacionesAprobadas as $key => $vinculacion) {
            $trabajadoresDelContratista = $trabajadoresFacturables->where('contratista_id', $vinculacion->contratista_id);
            
            if ($trabajadoresDelContratista->isNotEmpty()) {
                $resumen->push((object) [
                    'contratista_id' => $vinculacion->contratista_id,
                    'rut_contratista' => $vinculacion->rut_contratista,
                    'razon_social' => $vinculacion->razon_social,
                    'mandante_id' => $vinculacion->mandante_id,
                    'mandante_nombre' => $vinculacion->mandante_nombre,
                    'trabajadores_facturables' => $trabajadoresDelContratista->count(),
                ]);
                
                $detalleKey = $vinculacion->contratista_id . '-' . $vinculacion->mandante_id;
                $detalle[$detalleKey] = $trabajadoresDelContratista->sortBy('apellido_paterno')->values();
            }
        }

        $totalGeneral = $resumen->sum('trabajadores_facturables');

        return [
            'resumen' => $resumen->sortBy('razon_social')->values(),
            'detalle' => $detalle,
            'total_general' => $totalGeneral,
        ];
    }
}