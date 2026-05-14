<?php

namespace App\Imports;

use App\Models\ReglaDocumental;
use App\Models\Mandante;
use App\Models\TipoEntidadControlable;
use App\Models\NombreDocumento;
use App\Models\TipoVencimiento;
use App\Models\ObservacionDocumento;
use App\Models\FormatoDocumentoMuestra;
use App\Models\TipoCondicion;
use App\Models\TipoCondicionPersonal;
use App\Models\CondicionFechaIngreso;
use App\Models\CargoMandante;
use App\Models\Nacionalidad;
use App\Models\TipoPermanencia;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\TipoVehiculo;
use App\Models\TipoMaquinaria;
use App\Models\TipoEmbarcacion;
use App\Models\TenenciaVehiculo;
use App\Models\CriterioEvaluacion;
use App\Models\SubCriterio;
use App\Models\AclaracionCriterio;
use App\Models\TextoRechazo;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReglasDocumentalesImport implements ToCollection, WithHeadingRow, WithValidation
{
    public $successes = 0;
    public $updates = 0;
    public $failures = [];

    public function collection(Collection $rows)
    {
        // Agrupar filas por "Identidad de Regla"
        $grupos = $rows->groupBy(function ($row) {
            return $row['id'] ?: md5(($row['principal_mandante'] ?? '') . ($row['entidad'] ?? '') . ($row['nombre_documento'] ?? ''));
        });

        foreach ($grupos as $reglaIdLogico => $filasRegla) {
            $row = $filasRegla->first();
            $rowNumber = $rows->search($row) + 2;

            try {
                DB::beginTransaction();

                // 1. Resolver IDs desde nombres (Limpiamos posibles espacios extra)
                $valMandante = trim($row['principal_mandante'] ?? '');
                $valEntidad = trim($row['entidad'] ?? '');
                $valNombreDoc = trim($row['nombre_documento'] ?? '');

                $mandante_id = $this->resolveId(Mandante::class , 'razon_social', $valMandante);
                if (empty($valMandante))
                    throw new \Exception("La columna 'Principal (Mandante)' está vacía en la fila {$rowNumber}.");
                if (!$mandante_id)
                    throw new \Exception("El Mandante '{$valMandante}' no existe en el sistema.");

                $tipo_entidad_id = $this->resolveId(TipoEntidadControlable::class , 'nombre_entidad', $valEntidad);
                if (empty($valEntidad))
                    throw new \Exception("La columna 'Entidad' está vacía en la fila {$rowNumber}.");
                if (!$tipo_entidad_id)
                    throw new \Exception("El Tipo de Entidad '{$valEntidad}' no existe.");

                $nombre_doc_id = $this->resolveId(NombreDocumento::class , 'nombre', $valNombreDoc);
                if (empty($valNombreDoc))
                    throw new \Exception("La columna 'Nombre Documento' está vacía en la fila {$rowNumber}.");
                if (!$nombre_doc_id)
                    throw new \Exception("El Documento '{$valNombreDoc}' no existe en el catálogo maestro.");

                $data = [
                    'mandante_id' => $mandante_id,
                    'tipo_entidad_controlada_id' => $tipo_entidad_id,
                    'nombre_documento_id' => $nombre_doc_id,
                    'is_active' => strtolower($row['estado'] ?? '') === 'activa' ? 1 : 0,
                    'valor_nominal_documento' => $row['valor_nominal'] ?? null,
                    // NOTA: condicion_empresa y condicion_persona son BelongsToMany (pivot tables).
                    // Se sincronizan ABAJO con syncRelation(), NO como campos escalares aquí.
                    'condicion_fecha_ingreso_id' => $this->resolveId(CondicionFechaIngreso::class , 'nombre', $row['condicion_fecha_ingreso'] ?? ''),
                    'fecha_comparacion_ingreso' => $this->parseDate($row['fecha_comparacion_ingreso'] ?? null),
                    'dias_validez_documento' => $row['dias_validez'] ?? 0,
                    'dias_gracia_carga' => $row['dias_gracia'] ?? 0,
                    'dias_aviso_vencimiento' => $row['aviso_vencimiento'] ?? 0,
                    'tipo_vencimiento_id' => $this->resolveId(TipoVencimiento::class , 'nombre', $row['tipo_vencimiento'] ?? ''),
                    'valida_emision' => $this->parseBoolean($row['valida_emision'] ?? null),
                    'valida_vencimiento' => $this->parseBoolean($row['valida_vencimiento'] ?? null),
                    'requiere_validacion_mandante' => $this->parseBoolean($row['requiere_validacion_mandante'] ?? null),
                    'mostrar_historico_documento' => $this->parseBoolean($row['mostrar_historico'] ?? null),
                    'permite_ver_nacionalidad_trabajador' => $this->parseBoolean($row['permite_ver_nacionalidad'] ?? null),
                    'permite_modificar_nacionalidad_trabajador' => $this->parseBoolean($row['permite_modif_nacionalidad'] ?? null),
                    'permite_ver_fecha_nacimiento_trabajador' => $this->parseBoolean($row['permite_ver_f_nacimiento'] ?? null),
                    'permite_modificar_fecha_nacimiento_trabajador' => $this->parseBoolean($row['permite_modif_f_nacimiento'] ?? null),
                    'observacion_documento_id' => $this->resolveId(ObservacionDocumento::class , 'nombre', $row['observacion_documento'] ?? ''),
                    'formato_documento_id' => $this->resolveId(FormatoDocumentoMuestra::class , 'nombre', $row['formato_documento'] ?? ''),
                    'documento_relacionado_id' => $this->resolveId(NombreDocumento::class , 'nombre', $row['documento_relacionado'] ?? ''),
                    'rut_especificos' => $row['ruts_especificos'] ?? null,
                    'rut_excluidos' => $row['ruts_excluidos'] ?? null,
                    'imc_meses_estimados' => $row['duracion_meses_imc'] ?? null,
                    'user_id' => auth()->id(),
                ];

                // 2. Upsert de la Regla
                $reglaId = $row['id'] ?? null;
                $regla = null;
                if ($reglaId) {
                    $regla = ReglaDocumental::find($reglaId);
                    if ($regla) {
                        $regla->update($data);
                        $this->updates++;
                    }
                    else {
                        $regla = ReglaDocumental::create($data);
                        $this->successes++;
                    }
                }
                else {
                    $regla = ReglaDocumental::create($data);
                    $this->successes++;
                }

                // 3. Sincronizar Relaciones Many-to-Many
                // ─── Condiciones de Empresa y Trabajador (FIX: son BelongsToMany, no escalares) ───
                $this->syncRelation($regla, 'condicionesEmpresaAplica', TipoCondicion::class, 'nombre', $row['condicion_empresa'] ?? '');
                $this->syncRelation($regla, 'condicionesPersonaAplica', TipoCondicionPersonal::class, 'nombre', $row['condicion_persona'] ?? '');
                // ─────────────────────────────────────────────────────────────────────────────────
                $this->syncRelation($regla, 'cargosAplica', CargoMandante::class, 'nombre_cargo', $row['cargos_aplicables'] ?? '');
                $this->syncRelation($regla, 'nacionalidadesAplica', Nacionalidad::class, 'nombre', $row['nacionalidades_aplicables'] ?? '');
                $this->syncRelation($regla, 'tiposPermanenciaAplica', TipoPermanencia::class, 'nombre', $row['tipos_permanencia_aplicables'] ?? '');
                $this->syncRelation($regla, 'unidadesOrganizacionales', UnidadOrganizacionalMandante::class, 'nombre_unidad', $row['uos_aplicables'] ?? '');
                $this->syncRelation($regla, 'tiposVehiculoAplica', TipoVehiculo::class, 'nombre', $row['vehiculos_aplicables'] ?? '');
                $this->syncRelation($regla, 'tiposMaquinariaAplica', TipoMaquinaria::class, 'nombre', $row['maquinaria_aplicable'] ?? '');
                $this->syncRelation($regla, 'tiposEmbarcacionAplica', TipoEmbarcacion::class, 'nombre', $row['embarcaciones_aplicables'] ?? '');
                $this->syncRelation($regla, 'tenenciasAplica', TenenciaVehiculo::class, 'nombre', $row['tenencias_aplicables'] ?? '');

                // 4. Sincronizar Criterios de Evaluación
                $regla->criterios()->delete();
                
                // Agrupar filas por Criterio para procesar múltiples subcriterios
                $gruposCriterios = $filasRegla->groupBy(function($row) {
                    return md5(
                        ($row['criterio_de_evaluacion'] ?? '') . 
                        ($row['fuente_de_validacion'] ?? 'asem') . 
                        ($row['aclaracion_de_criterio'] ?? '') . 
                        ($row['texto_de_rechazo'] ?? '')
                    );
                });

                $orden = 1;
                foreach ($gruposCriterios as $filasCriterio) {
                    $primeraFila = $filasCriterio->first();
                    $criterio_id = $this->resolveId(CriterioEvaluacion::class , 'nombre_criterio', $primeraFila['criterio_de_evaluacion'] ?? '');
                    
                    if ($criterio_id) {
                        $nuevoCriterio = $regla->criterios()->create([
                            'fuente_validacion' => strtolower($primeraFila['fuente_de_validacion'] ?? 'asem'),
                            'criterio_evaluacion_id' => $criterio_id,
                            'sub_criterio_id' => null, // Obsoleto, pero se mantiene null por schema
                            'aclaracion_criterio_id' => $this->resolveId(AclaracionCriterio::class , 'titulo', $primeraFila['aclaracion_de_criterio'] ?? ''),
                            'texto_rechazo_id' => $this->resolveId(TextoRechazo::class , 'titulo', $primeraFila['texto_de_rechazo'] ?? ''),
                            'orden' => $orden++
                        ]);

                        // Attach Subcriterios (N-to-N pivot) condicionales
                        $subCriteriosPivot = [];
                        foreach ($filasCriterio as $fRow) {
                            $subCriterioId = $this->resolveId(SubCriterio::class, 'nombre', $fRow['subcriterio'] ?? '');
                            if ($subCriterioId) {
                                // Resolviendo condiciones (null o "Universal" significa aplica a todos)
                                $condPersRaw = $fRow['condicion_trabajador_subcriterio'] ?? '';
                                $condEmpRaw = $fRow['condicion_empresa_subcriterio'] ?? '';
                                
                                $condPersId = (strtolower(trim($condPersRaw)) === 'universal' || empty(trim($condPersRaw))) 
                                    ? null : $this->resolveId(TipoCondicionPersonal::class, 'nombre', $condPersRaw);
                                    
                                $condEmpId = (strtolower(trim($condEmpRaw)) === 'universal' || empty(trim($condEmpRaw))) 
                                    ? null : $this->resolveId(TipoCondicion::class, 'nombre', $condEmpRaw);

                                $subCriteriosPivot[] = [
                                    'sub_criterio_id' => $subCriterioId,
                                    'tipo_condicion_personal_id' => $condPersId,
                                    'tipo_condicion_id' => $condEmpId,
                                ];
                            }
                        }
                        
                        if (!empty($subCriteriosPivot)) {
                            $nuevoCriterio->subCriterios()->attach($subCriteriosPivot);
                        }
                    }
                }

                DB::commit();
            }
            catch (\Exception $e) {
                DB::rollBack();
                $this->failures[] = [
                    'row' => $rowNumber,
                    'errors' => $e->getMessage(),
                    'id' => $reglaId ?: 'Nuevo'
                ];
            }
        }
    }

    private function resolveId($model, $column, $value)
    {
        if (empty(trim($value)))
            return null;
        $item = $model::where($column, trim($value))->first();
        return $item ? $item->id : null;
    }

    private function syncRelation($model, $relation, $targetModel, $column, $valuesString)
    {
        if (empty($valuesString)) {
            $model->$relation()->sync([]);
            return;
        }

        $names = array_map('trim', explode(',', $valuesString));
        $ids = $targetModel::whereIn($column, $names)->pluck('id')->toArray();
        $model->$relation()->sync($ids);
    }

    private function parseBoolean($value)
    {
        if (is_null($value) || $value === '')
            return 0;
        if (is_bool($value))
            return $value ? 1 : 0;
        $val = strtolower(trim($value));
        return in_array($val, ['si', 'sí', '1', 'true', 'activo', 'activa']) ? 1 : 0;
    }

    private function parseDate($value)
    {
        if (empty($value))
            return null;
        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.principal_mandante' => 'required',
            '*.entidad' => 'required',
            '*.nombre_documento' => 'required',
            '*.tipo_vencimiento' => 'required',
            '*.valor_nominal' => 'required',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Normalización de claves para evitar fallos por caracteres especiales o espacios
        $normalizedData = [];
        foreach ($data as $key => $value) {
            $newKey = $key;
            // Si el key contiene "principal" y "mandante", lo forzamos a principal_mandante
            if (str_contains($key, 'principal') && str_contains($key, 'mandante')) {
                $newKey = 'principal_mandante';
            }
            if ($key === 'id') $newKey = 'id';
            if ($key === 'entidad') $newKey = 'entidad';
            if (str_contains($key, 'nombre') && str_contains($key, 'documento') && !str_contains($key, 'relacionado') && !str_contains($key, 'observacion')) {
                $newKey = 'nombre_documento';
            }
            
            $normalizedData[$newKey] = $value;
        }
        return $normalizedData;
    }
}
