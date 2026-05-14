<?php

namespace App\Services;

use App\Services\IaCamposDisponibles;
use App\Models\DocumentoCargado;
use App\Models\DatoExtraidoIa;
use App\Models\IaCampoConfiguracion;
use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Log;

/**
 * IaMatchService — Motor de comparación campo a campo.
 *
 * Compara los datos extraídos por IA (datos_extraidos_ia.datos_extraidos)
 * contra los datos reales de la entidad en la base de datos.
 *
 * NO modifica documentos_cargados. Solo calcula el resultado del match
 * y lo escribe en datos_extraidos_ia.
 */
class IaMatchService
{
    /**
     * Ejecuta el match para un DatoExtraidoIa dado.
     * Actualiza match_calculado, detalle_match, observacion_match y estado.
     */
    public function calcularMatch(DatoExtraidoIa $datoIa): DatoExtraidoIa
    {
        $documento = $datoIa->documentoCargado;

        if (!$documento) {
            Log::error("[IaMatchService] No se encontró documento_cargado para DatoExtraidoIa ID {$datoIa->id}");
            throw new \RuntimeException('Documento no encontrado para el dato IA.');
        }

        // Cargar campos configurados para esta regla
        $campos = IaCampoConfiguracion::where('regla_documental_id', $documento->regla_documental_id_origen)
            ->where('is_active', true)
            ->orderBy('orden')
            ->get();

        if ($campos->isEmpty()) {
            Log::warning("[IaMatchService] La regla {$documento->regla_documental_id_origen} no tiene campos IA configurados.");
            $datoIa->update([
                'match_calculado'  => 'REVISION_MANUAL',
                'detalle_match'    => [],
                'observacion_match'=> 'Sin campos IA configurados para esta regla. Requiere revisión manual.',
                'estado'           => 'MATCH_CALCULADO',
            ]);
            return $datoIa->refresh();
        }

        // Obtener datos del RUT de la entidad real en DB
        $datosEntidad = $this->obtenerDatosEntidad($documento);

        $datosExtraidos = $datoIa->datos_extraidos ?? [];
        $detalle        = [];
        $hayFallo       = false;
        $hayRevision    = false;

        foreach ($campos as $campo) {
            $clave     = $campo->campo_clave;

            if ($campo->esCriterio()) {
                // Nuevo Paradigma: La IA actúa como auditora booleana ("SI" o "NO") pero también extrae la evidencia
                $esperado = 'SI';
                $tipoComparacion = 'boolean_ia';
                
                $extraidoTexto = $datosExtraidos[$clave . '_extraido'] ?? null;
                $extraidoCumple = $datosExtraidos[$clave . '_cumple'] ?? null;

                $item = [
                    'campo'    => $campo->etiqueta,
                    'clave'    => $clave,
                    'extraido' => $extraidoTexto, // La evidencia textual (RUT, Nombres, etc)
                    'cumple_ia'=> $extraidoCumple, // SÍ o NO
                    'esperado' => $esperado,
                    'ok'       => false,
                    'mensaje'  => '',
                ];

                if (is_null($extraidoCumple) || $extraidoCumple === '') {
                    $item['ok'] = !$campo->es_requerido;
                    $item['mensaje'] = $campo->es_requerido ? 'Evaluación SI/NO no encontrada' : 'Campo no evaluado';
                    if ($campo->es_requerido) $hayFallo = true;
                } else {
                    $item['ok'] = $this->comparar($tipoComparacion, $extraidoCumple, $esperado, $datosEntidad);
                    if (!$item['ok']) {
                        $item['mensaje'] = "La IA evaluó el criterio como NO CUMPLE.";
                        if ($campo->es_requerido) $hayFallo = true;
                    } else {
                        $item['mensaje'] = "Coincide";
                    }
                }
                $detalle[] = $item;
                continue;
            } else {
                // Campo estándar (fecha, periodo, rut)
                $esperado = $datosEntidad[$clave] ?? null;
                $tipoComparacion = $campo->tipo_dato;
                $extraido = $datosExtraidos[$clave] ?? null;

                $item = [
                    'campo'    => $campo->etiqueta,
                    'clave'    => $clave,
                    'extraido' => $extraido,
                    'esperado' => $esperado,
                    'ok'       => false,
                    'mensaje'  => '',
                ];

                if (is_null($extraido) || $extraido === '') {
                    $item['ok']      = !$campo->es_requerido; // ok solo si no es requerido
                    $item['mensaje'] = $campo->es_requerido ? 'Campo requerido no encontrado' : 'Campo no encontrado (no requerido)';
                    if ($campo->es_requerido) {
                        $hayFallo = true;
                    }
                    $detalle[] = $item;
                    continue;
                }

                // Si no hay valor esperado en la DB para comparar
                if (is_null($esperado) || $esperado === '') {
                    $item['ok']      = true; // Damos por bueno, no podemos comparar
                    $item['mensaje'] = 'Sin referencia en BD para comparar. Aceptado.';
                    $hayRevision     = true;
                    $detalle[]       = $item;
                    continue;
                }

                // Las fechas y el periodo son campos informativos que sobrescribirán la BD.
                // Si fueron extraídos, no penalizan el match, incluso si son diferentes a lo ingresado por el contratista.
                if (in_array($clave, ['fecha_emision', 'fecha_vencimiento', 'periodo'])) {
                    $item['ok']      = true;
                    $item['mensaje'] = 'Se actualizará en BD al confirmar';
                    $detalle[]       = $item;
                    continue;
                }

                // Comparar según el tipo
                $ok = $this->comparar($tipoComparacion, $extraido, $esperado, $datosEntidad);

                $item['ok']      = $ok;
                $item['mensaje'] = $ok ? 'Coincide' : 'No coincide';

                if (!$ok && $campo->es_requerido) {
                    $hayFallo = true;
                }

                $detalle[] = $item;
            }
        }

        // Determinar resultado global
        if ($hayFallo) {
            $resultado = 'RECHAZADO';
        } elseif ($hayRevision) {
            $resultado = 'REVISION_MANUAL';
        } else {
            $resultado = 'APROBADO';
        }

        $datoIa->update([
            'match_calculado'   => $resultado,
            'detalle_match'     => $detalle,
            'observacion_match' => $this->generarTextoObservacion($datoIa, $detalle, $resultado),
            'estado'            => 'MATCH_CALCULADO',
        ]);

        Log::info("[IaMatchService] Match calculado para Doc ID {$documento->id}: {$resultado}");

        return $datoIa->refresh();
    }

    /**
     * Confirma el match y escribe el resultado en documentos_cargados.
     * Replica exactamente la lógica de RevisarDocumento::aprobarDocumentoAsem().
     */
    public function confirmarMatch(DatoExtraidoIa $datoIa, int $usuarioConfirmaId): void
    {
        $documento = $datoIa->documentoCargado;

        if (!$documento) {
            throw new \RuntimeException('Documento no encontrado.');
        }

        if (!in_array($datoIa->match_calculado, ['APROBADO', 'RECHAZADO', 'REVISION_MANUAL'])) {
            throw new \RuntimeException('El match no ha sido calculado aún.');
        }

        // Obtener usuario IA_validator
        $iaValidatorId = $this->getIaValidatorId();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $textoObservacion = $datoIa->observacion_match ?? $datoIa->generarTextoObservacion();

            if ($datoIa->match_calculado === 'APROBADO') {
                $updateData = $this->buildAprobadoData($datoIa, $documento, $iaValidatorId, $textoObservacion);

                $documento->update($updateData);

                // ► CRÍTICO: archivar doc anterior si este es un reemplazo (idéntico a RevisarDocumento)
                if ($documento->reemplaza_a_id) {
                    $documentoOriginal = DocumentoCargado::find($documento->reemplaza_a_id);
                    if ($documentoOriginal) {
                        $documentoOriginal->update(['estado_validacion' => 'Archivado']);
                        Log::info("[IaMatchService] Archivado doc original ID {$documentoOriginal->id} por reemplazo.");
                    }
                }

            } else {
                // RECHAZADO o REVISION_MANUAL → se trata como Rechazado
                $updateData = $this->buildRechazadoData($datoIa, $documento, $iaValidatorId, $textoObservacion);
                $documento->update($updateData);
            }

            // Actualizar estado en datos_extraidos_ia
            $datoIa->update([
                'estado'             => 'CONFIRMADO',
                'usuario_confirma_id'=> $usuarioConfirmaId,
                'fecha_confirmacion' => now(),
            ]);

            \Illuminate\Support\Facades\DB::commit();

            // Recalcular estado del recurso (igual que el validador humano)
            if ($documento->entidad) {
                \App\Jobs\ActualizarEstadoRecursoIndividual::dispatch($documento->entidad);
            }

            // Auditoría
            \App\Services\AuditService::log(
                'validacion-ia-acreditacion',
                "IA_ACRED [{$datoIa->match_calculado}]: " .
                    ($documento->nombre_documento_snapshot ?? 'N/A') .
                    " | Entidad ID: {$documento->entidad_id} | Doc ID: {$documento->id}",
                [
                    'documento_id'       => $documento->id,
                    'resultado'          => $datoIa->match_calculado,
                    'ia_dato_id'         => $datoIa->id,
                    'fuente'             => $datoIa->fuente,
                    'proveedor'          => $datoIa->proveedor_ia,
                    'usuario_confirma'   => $usuarioConfirmaId,
                ]
            );

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error("[IaMatchService] Error al confirmar match Doc ID {$documento->id}: " . $e->getMessage());
            throw $e;
        }
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function buildAprobadoData(DatoExtraidoIa $datoIa, DocumentoCargado $documento, int $iaValidatorId, string $textoObs): array
    {
        $datosExtraidos = $datoIa->datos_extraidos ?? [];

        $data = [
            'asem_validador_id'    => $iaValidatorId,
            'resultado_validacion' => 'Aprobado',
            'fecha_validacion'     => now(),
            'observacion_rechazo'  => null,
            'observacion_validador'=> $textoObs,
            'estado_validacion'    => $documento->estado_validacion === 'Asignado-Revalidar'
                                       ? 'Revisado-Revalidado'
                                       : 'Revisado',
        ];

        // Escribir columnas extraídas directamente en documentos_cargados
        // usando el catálogo IaCamposDisponibles para saber qué columna corresponde
        foreach ($datosExtraidos as $campoClave => $valor) {
            $def = IaCamposDisponibles::definicion($campoClave);
            if ($def && $def['mapea_columna'] && !is_null($valor)) {
                $data[$def['mapea_columna']] = $valor;
            }
        }

        return $data;
    }

    private function buildRechazadoData(DatoExtraidoIa $datoIa, DocumentoCargado $documento, int $iaValidatorId, string $textoObs): array
    {
        // Construir texto de rechazo con campos fallidos
        $detalles  = $datoIa->detalle_match ?? [];
        $fallidos  = array_filter($detalles, fn($d) => !$d['ok']);
        $motivoTxt = implode("\n- ", array_map(fn($d) => "{$d['campo']}: {$d['mensaje']}", $fallidos));
        $rechazoFinal = "Motivos de rechazo (IA):\n- " . ($motivoTxt ?: 'No coinciden los datos extraídos');

        return [
            'asem_validador_id'    => $iaValidatorId,
            'resultado_validacion' => 'Rechazado',
            'fecha_validacion'     => now(),
            'observacion_rechazo'  => $rechazoFinal,
            'observacion_validador'=> $textoObs,
            'estado_validacion'    => $documento->reemplaza_a_id
                                       ? 'Archivado'
                                       : ($documento->estado_validacion === 'Asignado-Revalidar'
                                           ? 'Revisado-Revalidado'
                                           : 'Revisado'),
        ];
    }

    /**
     * Obtiene los datos de referencia de la entidad para comparar con los extraídos.
     * El campo 'entidad_rut' es el RUT real de la entidad.
     * El campo 'entidad_identificacion' contiene el nombre completo y RUT (para fuzzy match).
     */
    private function obtenerDatosEntidad(DocumentoCargado $documento): array
    {
        $datos   = [];
        $entidad = $documento->entidad;

        if (!$entidad) return $datos;

        // Todos los tipos de entidad exponen 'entidad_rut' y construimos su identificación completa
        if ($entidad instanceof Trabajador) {
            $datos['entidad_rut'] = $entidad->rut ?? null;
            $datos['entidad_identificacion'] = trim(($entidad->nombres ?? '') . ' ' . ($entidad->apellidos ?? '') . ' ' . ($entidad->rut ?? ''));
        } elseif ($entidad instanceof Contratista) {
            $datos['entidad_rut'] = $entidad->rut ?? null;
            $datos['entidad_identificacion'] = trim(($entidad->razon_social ?? '') . ' ' . ($entidad->rut ?? ''));
        } elseif ($entidad instanceof Vehiculo) {
            $datos['entidad_rut'] = ($entidad->patente_letras ?? '') . ($entidad->patente_numeros ?? '');
            $datos['entidad_identificacion'] = trim(($entidad->marca ?? '') . ' ' . ($entidad->modelo ?? '') . ' ' . $datos['entidad_rut']);
        }

        // Período del documento (si aplica)
        if ($documento->periodo) {
            $datos['periodo'] = $documento->periodo;
        }

        // Fechas actuales del documento (para comparación si el operador quiere verificarlas)
        if ($documento->fecha_emision) {
            $datos['fecha_emision'] = $documento->fecha_emision->format('Y-m-d');
        }
        if ($documento->fecha_vencimiento) {
            $datos['fecha_vencimiento'] = $documento->fecha_vencimiento->format('Y-m-d');
        }

        return $datos;
    }

    private function comparar(string $tipoDato, mixed $extraido, mixed $esperado, array $datosEntidad = []): bool
    {
        if ($tipoDato === 'boolean_ia') {
            // El usuario extrajo un SI o NO.
            // Para ser aprobado, debe contener alguna afirmación.
            $ext = strtolower(trim((string)$extraido));
            return str_contains($ext, 'si') || 
                   str_contains($ext, 'sí') || 
                   str_contains($ext, 'yes') || 
                   str_contains($ext, 'ok') ||
                   str_contains($ext, 'cumple') ||
                   str_contains($ext, 'aprobado') ||
                   str_contains($ext, 'verdadero') ||
                   str_contains($ext, 'true');
        }
        if ($tipoDato === 'criterio_lista') {
            // $esperado es "Valor 1, Valor 2". Extraido debe contener al menos uno.
            $valores = array_map('trim', explode(',', (string)$esperado));
            $ext = strtolower(trim((string)$extraido));
            foreach ($valores as $v) {
                if ($v !== '' && str_contains($ext, strtolower($v))) {
                    return true;
                }
            }
            return false;
        }

        if ($tipoDato === 'identidad_fuzzy') {
            // El usuario extrajo información de identidad. 
            // Buscamos si el RUT real está dentro de lo extraído, O si contiene parte significativa del nombre.
            $ext = strtolower(trim((string)$extraido));
            $rutNormalizado = $this->normalizarRut($datosEntidad['entidad_rut'] ?? '');
            
            // 1. Si el RUT normalizado está en el texto extraído (normalizado), es match fuerte.
            if ($rutNormalizado && str_contains($this->normalizarRut($ext), $rutNormalizado)) {
                return true;
            }

            // 2. Fallback: Verificamos si al menos 2 palabras de la identificación real están en el texto
            $partesId = array_filter(explode(' ', strtolower((string)$esperado)));
            $coincidencias = 0;
            foreach ($partesId as $parte) {
                if (strlen($parte) > 2 && str_contains($ext, $parte)) {
                    $coincidencias++;
                }
            }
            
            return $coincidencias >= 2;
        }

        return match($tipoDato) {
            'rut'   => $this->normalizarRut((string)$extraido) === $this->normalizarRut((string)$esperado),
            'fecha' => $this->normalizarFecha((string)$extraido) === $this->normalizarFecha((string)$esperado),
            default => strtolower(trim((string)$extraido)) === strtolower(trim((string)$esperado)),
        };
    }

    private function normalizarRut(string $rut): string
    {
        return strtoupper(str_replace(['.', '-', ' '], '', $rut));
    }

    private function normalizarFecha(string $fecha): string
    {
        try {
            return \Carbon\Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Exception $e) {
            return strtolower(trim($fecha));
        }
    }

    private function generarTextoObservacion(DatoExtraidoIa $datoIa, array $detalle, string $resultado): string
    {
        $proveedor = $datoIa->proveedor_ia ?? 'IA';
        $fecha     = now()->format('Y-m-d H:i');
        $lines     = ["Revisado por IA ({$proveedor}) — {$fecha}"];

        foreach ($detalle as $item) {
            $icono = $item['ok'] ? '✓' : '✗';
            if ($item['ok']) {
                $lines[] = "{$icono} {$item['campo']}: {$item['extraido']} (coincide)";
            } else {
                $lines[] = "{$icono} {$item['campo']}: extraído=[{$item['extraido']}] | esperado=[{$item['esperado']}]";
            }
        }

        $lines[] = '';
        $lines[] = "RESULTADO: {$resultado}";

        return implode("\n", $lines);
    }

    private function getIaValidatorId(): int
    {
        $user = \App\Models\User::where('email', 'ia@sistema.internal')->first();

        if (!$user) {
            throw new \RuntimeException(
                'Usuario IA_validator no encontrado. Ejecuta: php artisan db:seed --class=IaValidatorUserSeeder'
            );
        }

        return $user->id;
    }
}
