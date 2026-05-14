<?php

namespace App\Imports;

use App\Models\DocumentoCargado;
use App\Models\DatoExtraidoIa;
use App\Models\IaCampoConfiguracion;
use App\Services\IaMatchService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

/**
 * DatosIaExcelImport — Importador Excel para extracción manual de datos IA.
 *
 * Formato esperado del Excel (N filas):
 *   Columna A: documento_cargado_id  (obligatorio)
 *   Columna B..N: campos configurados en ia_campos_configuracion para esa regla
 *
 * Cada fila = 1 documento_cargado.
 * Al importar: guarda en datos_extraidos_ia y calcula el match automáticamente.
 */
class DatosIaExcelImport implements ToCollection, WithHeadingRow
{
    /** Resultados del proceso para mostrar en la UI */
    public array $resultados   = [];
    public int   $procesados   = 0;
    public int   $errores      = 0;
    public int   $omitidos     = 0;

    public function __construct(
        private readonly IaMatchService $matchService
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $rowArray = $row->toArray();

            // Normalizar claves (quitar espacios, lowercase)
            $rowArray = array_combine(
                array_map(fn($k) => strtolower(trim(str_replace(' ', '_', $k))), array_keys($rowArray)),
                array_values($rowArray)
            );

            $docId = $rowArray['documento_cargado_id'] ?? null;

            if (!$docId || !is_numeric($docId)) {
                $this->omitidos++;
                $this->resultados[] = [
                    'doc_id'   => $docId ?? '—',
                    'estado'   => 'OMITIDO',
                    'mensaje'  => 'documento_cargado_id inválido o vacío.',
                    'color'    => 'gray',
                ];
                continue;
            }

            $documento = DocumentoCargado::find((int)$docId);

            if (!$documento) {
                $this->errores++;
                $this->resultados[] = [
                    'doc_id'   => $docId,
                    'estado'   => 'ERROR',
                    'mensaje'  => "Documento ID {$docId} no encontrado.",
                    'color'    => 'red',
                ];
                continue;
            }

            try {
                // Obtener campos configurados para la regla de este documento
                $camposRegla = IaCampoConfiguracion::where('regla_documental_id', $documento->regla_documental_id_origen)
                    ->where('is_active', true)
                    ->orderBy('orden')
                    ->get();

                if ($camposRegla->isEmpty()) {
                    $this->omitidos++;
                    $this->resultados[] = [
                        'doc_id'   => $docId,
                        'nombre'   => $documento->nombre_documento_snapshot,
                        'estado'   => 'OMITIDO',
                        'mensaje'  => 'La regla de este documento no tiene campos IA configurados.',
                        'color'    => 'yellow',
                    ];
                    continue;
                }

                // Extraer solo los campos configurados de la fila
                $datosExtraidos = [];
                foreach ($camposRegla as $campo) {
                    $clave = strtolower($campo->campo_clave);
                    if (array_key_exists($clave, $rowArray)) {
                        $valor = $rowArray[$clave];
                        // Limpiar valores vacíos de Excel
                        $datosExtraidos[$campo->campo_clave] = ($valor === '' || $valor === null) ? null : $valor;
                    }
                }

                // Guardar / actualizar en datos_extraidos_ia
                $datoIa = DatoExtraidoIa::updateOrCreate(
                    ['documento_cargado_id' => $documento->id],
                    [
                        'fuente'             => 'EXCEL',
                        'proveedor_ia'       => null,
                        'datos_extraidos'    => $datosExtraidos,
                        'respuesta_cruda_ia' => null,
                        'tokens_entrada'     => null,
                        'tokens_salida'      => null,
                        'costo_estimado_usd' => null,
                        'match_calculado'    => null,
                        'detalle_match'      => null,
                        'observacion_match'  => null,
                        'estado'             => 'EXTRAIDO',
                        'usuario_confirma_id'=> null,
                        'fecha_confirmacion' => null,
                    ]
                );

                // Calcular match inmediatamente
                $datoIa = $this->matchService->calcularMatch($datoIa);

                $this->procesados++;
                $this->resultados[] = [
                    'doc_id'   => $docId,
                    'nombre'   => $documento->nombre_documento_snapshot,
                    'estado'   => $datoIa->match_calculado,
                    'mensaje'  => "Match: {$datoIa->match_calculado}",
                    'color'    => match($datoIa->match_calculado) {
                        'APROBADO'        => 'green',
                        'RECHAZADO'       => 'red',
                        'REVISION_MANUAL' => 'yellow',
                        default           => 'gray',
                    },
                ];

            } catch (\Exception $e) {
                $this->errores++;
                Log::error("[DatosIaExcelImport] Error fila doc_id={$docId}: " . $e->getMessage());
                $this->resultados[] = [
                    'doc_id'   => $docId,
                    'nombre'   => $documento->nombre_documento_snapshot ?? '—',
                    'estado'   => 'ERROR',
                    'mensaje'  => $e->getMessage(),
                    'color'    => 'red',
                ];
            }
        }
    }
}
