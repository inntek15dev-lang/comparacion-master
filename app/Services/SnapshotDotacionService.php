<?php

namespace App\Services;

use App\Models\VerificacionHistorica;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CarpetaVerificacion;
use App\Models\CarpetaVerificacionTrabajador;
use App\Models\TrabajadorVinculacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SnapshotDotacionService
 *
 * Crea los registros de "Dotación Anterior" (tipo DOTACION_ANTERIOR) en la primera
 * carpeta de verificación activa de cada contrato vinculado a un ID_REGISTRO.
 *
 * REGLA CRÍTICA: Las retenciones NO se arrastran entre períodos.
 * Este servicio sólo marca la presencia del trabajador en el período anterior
 * como referencia histórica. NO genera retenciones automáticas.
 *
 * tipo_registro = 'DOTACION_ANTERIOR' (nuevo valor junto a los existentes VIGENTE / ARRASTRE)
 */
class SnapshotDotacionService
{
    /** Resumen de la ejecución */
    public array $resumen = [
        'carpetas_procesadas'  => 0,
        'trabajadores_creados' => 0,
        'carpetas_omitidas'    => 0, // ya tenían dotación
        'advertencias'         => [],
    ];

    /**
     * Procesa todos los id_registro de las verificaciones históricas importadas
     * y puebla la primera carpeta de verificación activa que esté vacía.
     *
     * @param  array<string> $idRegistros  Lista de id_registro a procesar (vacío = todos)
     * @param  int|null      $mandanteId   Filtrar por mandante (null = todos)
     */
    public function ejecutar(array $idRegistros = [], ?int $mandanteId = null): void
    {
        $this->resumen = [
            'carpetas_procesadas'  => 0,
            'trabajadores_creados' => 0,
            'carpetas_omitidas'    => 0,
            'advertencias'         => [],
        ];

        // Obtener los id_registros únicos con su último período histórico
        $query = VerificacionHistorica::query()
            ->select('id_registro', 'mandante_id', 'lugar', 'contrato')
            ->when(!empty($idRegistros), fn($q) => $q->whereIn('id_registro', $idRegistros))
            ->when($mandanteId, fn($q) => $q->where('mandante_id', $mandanteId))
            ->distinct();

        $registros = $query->get();

        foreach ($registros as $registro) {
            $this->procesarRegistro(
                $registro->id_registro,
                $registro->mandante_id,
                $registro->lugar,
                $registro->contrato
            );
        }
    }

    /**
     * Procesa un id_registro individual:
     * 1. Busca el CUO correspondiente en contratista_unidad_organizacional
     * 2. Encuentra la primera CarpetaVerificacion activa sin trabajadores
     * 3. Carga los TrabajadorVinculacion activos y crea snapshots
     */
    private function procesarRegistro(
        string $idRegistro,
        int    $mandanteId,
        string $lugar,
        string $contrato
    ): void {
        try {
            // ── PASO 1: Encontrar la CUO vinculada al id_registro ─────────────
            // El id_registro es único por contratista dentro de un mandante.
            // Puede haber varias CUO con el mismo id_registro (subcontratos, etc.)
            $cuos = ContratistaUnidadOrganizacional::where('id_registro', $idRegistro)
                ->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $mandanteId))
                ->get();

            if ($cuos->isEmpty()) {
                $this->resumen['advertencias'][] = "ID_REGISTRO '$idRegistro': no se encontró vinculación activa en el sistema.";
                return;
            }

            foreach ($cuos as $cuo) {
                $this->procesarCuo($cuo, $idRegistro, $lugar, $contrato);
            }

        } catch (\Throwable $e) {
            Log::error("SnapshotDotacionService - Error en id_registro '$idRegistro': " . $e->getMessage());
            $this->resumen['advertencias'][] = "Error en '$idRegistro': " . $e->getMessage();
        }
    }

    private function procesarCuo(
        ContratistaUnidadOrganizacional $cuo,
        string $idRegistro,
        string $lugar,
        string $contrato
    ): void {
        // ── PASO 2: Buscar la PRIMERA carpeta de verificación activa ──────────
        // La primera carpeta se determina como la de menor (anio, mes) que esté
        // en estado que permita modificaciones (no ENVIADO aún, o recién creada).
        $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $cuo->id)
            ->orderBy('anio', 'asc')
            ->orderBy('mes', 'asc')
            ->first();

        if (!$carpeta) {
            $this->resumen['advertencias'][] = "ID_REGISTRO '$idRegistro' (CUO #{$cuo->id}): no tiene carpeta de verificación creada aún. Se omite el snapshot.";
            $this->resumen['carpetas_omitidas']++;
            return;
        }

        // ── PASO 3: Verificar si la carpeta ya tiene dotación ─────────────────
        // Si ya tiene registros DOTACION_ANTERIOR, no duplicamos.
        $yaExisteDotacion = CarpetaVerificacionTrabajador::where('carpeta_verificacion_id', $carpeta->id)
            ->where('tipo_registro', 'DOTACION_ANTERIOR')
            ->exists();

        if ($yaExisteDotacion) {
            $this->resumen['carpetas_omitidas']++;
            return; // Idempotente: ya fue procesada
        }

        // ── PASO 4: Obtener trabajadores activos de esta CUO ─────────────────
        // Los trabajadores de la "Dotación Anterior" son aquellos que estaban
        // activos en la vinculación al momento de la migración.
        $vinculaciones = TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $cuo->unidad_organizacional_mandante_id)
            ->where('dependencia_id', $cuo->dependencia_id)
            ->where('numero_contrato', $cuo->numero_contrato)
            ->where('is_active', true)
            ->whereHas('trabajador', fn($q) => $q->where('contratista_id', $cuo->contratista_id))
            ->get();

        if ($vinculaciones->isEmpty()) {
            $this->resumen['advertencias'][] = "ID_REGISTRO '$idRegistro' (CUO #{$cuo->id}): sin trabajadores activos para snapshot. Carpeta #{$carpeta->id} omitida.";
            $this->resumen['carpetas_omitidas']++;
            return;
        }

        // ── PASO 5: Crear registros DOTACION_ANTERIOR ─────────────────────────
        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($vinculaciones as $vinculacion) {
                // Verificar que no exista ya un registro para este trabajador en esta carpeta
                $existe = CarpetaVerificacionTrabajador::where('carpeta_verificacion_id', $carpeta->id)
                    ->where('trabajador_vinculacion_id', $vinculacion->id)
                    ->exists();

                if ($existe) {
                    continue; // Idempotente por trabajador
                }

                CarpetaVerificacionTrabajador::create([
                    'carpeta_verificacion_id'           => $carpeta->id,
                    'trabajador_vinculacion_id'          => $vinculacion->id,
                    'destino_trabajador_vinculacion_id'   => null,
                    'snapshot_rut'                      => $vinculacion->trabajador->rut,
                    'snapshot_nombres'                  => $vinculacion->trabajador->nombre_completo,
                    'snapshot_cargo'                    => $vinculacion->cargoMandante?->nombre_cargo ?? 'CARGO NO REGISTRADO',
                    'snapshot_fecha_ingreso'            => $vinculacion->fecha_ingreso_vinculacion,
                    'snapshot_fecha_contrato'           => $vinculacion->fecha_contrato,
                    'tipo_registro'                     => 'DOTACION_ANTERIOR',
                    'estado_revision'                   => 'PENDIENTE',
                    'observaciones'                     => null,
                ]);
                $count++;
            }

            DB::commit();
            $this->resumen['carpetas_procesadas']++;
            $this->resumen['trabajadores_creados'] += $count;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("SnapshotDotacionService - Error creando snapshot CUO #{$cuo->id}: " . $e->getMessage());
            $this->resumen['advertencias'][] = "Error creando snapshot (CUO #{$cuo->id}): " . $e->getMessage();
        }
    }

    /**
     * Preview: cuántos trabajadores SE CREARÍAN si se ejecuta el snapshot.
     * Útil para mostrar al operador antes de confirmar.
     */
    public function preview(?int $mandanteId = null): array
    {
        $query = VerificacionHistorica::query()
            ->select('id_registro', 'mandante_id')
            ->when($mandanteId, fn($q) => $q->where('mandante_id', $mandanteId))
            ->distinct();

        $total = 0;
        $detalles = [];

        foreach ($query->get() as $registro) {
            $cuos = ContratistaUnidadOrganizacional::where('id_registro', $registro->id_registro)
                ->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $registro->mandante_id))
                ->get();

            foreach ($cuos as $cuo) {
                $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $cuo->id)
                    ->orderBy('anio')->orderBy('mes')->first();

                if (!$carpeta) continue;

                $existeDotacion = CarpetaVerificacionTrabajador::where('carpeta_verificacion_id', $carpeta->id)
                    ->where('tipo_registro', 'DOTACION_ANTERIOR')
                    ->exists();

                if ($existeDotacion) continue;

                $count = TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $cuo->unidad_organizacional_mandante_id)
                    ->where('dependencia_id', $cuo->dependencia_id)
                    ->where('numero_contrato', $cuo->numero_contrato)
                    ->where('is_active', true)
                    ->whereHas('trabajador', fn($q) => $q->where('contratista_id', $cuo->contratista_id))
                    ->count();

                $total += $count;
                $detalles[] = [
                    'id_registro' => $registro->id_registro,
                    'carpeta_id'  => $carpeta->id,
                    'periodo'     => $carpeta->anio . '-' . str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT),
                    'trabajadores' => $count,
                ];
            }
        }

        return [
            'total_trabajadores' => $total,
            'total_carpetas'     => count($detalles),
            'detalles'           => $detalles,
        ];
    }
}
