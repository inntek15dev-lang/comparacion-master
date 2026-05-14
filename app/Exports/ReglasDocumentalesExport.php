<?php

namespace App\Exports;

use App\Models\ReglaDocumental;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Spatie\Activitylog\Models\Activity;

class ReglasDocumentalesExport implements WithMultipleSheets
{
    protected $filtros;
    protected $idsSeleccionados;
    protected $incluirHistorial;
    protected $soloHistorial;

    public function __construct(array $filtros = [], array $idsSeleccionados = [], bool $incluirHistorial = false, bool $soloHistorial = false)
    {
        $this->filtros = $filtros;
        $this->idsSeleccionados = array_filter($idsSeleccionados);
        $this->incluirHistorial = $incluirHistorial;
        $this->soloHistorial = $soloHistorial;
    }

    public function sheets(): array
    {
        if ($this->soloHistorial) {
            return [new HistorialSheet($this->filtros, $this->idsSeleccionados)];
        }

        $sheets = [
            new ReglasSheet($this->filtros, $this->idsSeleccionados)
        ];

        if ($this->incluirHistorial) {
            $sheets[] = new HistorialSheet($this->filtros, $this->idsSeleccionados);
        }

        return $sheets;
    }
}

class ReglasSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $filtros;
    protected $idsSeleccionados;

    public function __construct($filtros, $idsSeleccionados)
    {
        $this->filtros = $filtros;
        $this->idsSeleccionados = $idsSeleccionados;
    }

    public function collection()
    {
        $reglas = ReglaDocumental::query()
            ->with([
            'mandante', 'tipoEntidadControlada', 'nombreDocumento',
            'unidadesOrganizacionales', 'cargosAplica', 'nacionalidadesAplica', 'tiposPermanenciaAplica',
            'tipoVencimiento', 'observacionDocumento', 'formatoDocumento',
            'condicionesEmpresaAplica', 'condicionesPersonaAplica',
            'tiposVehiculoAplica', 'tiposMaquinariaAplica', 'tiposEmbarcacionAplica', 'tenenciasAplica',
            'criterios.criterioEvaluacion', 'criterios.subCriterios', 'criterios.aclaracionCriterio', 'criterios.textoRechazo',
            'condicionFechaIngreso', 'documentoRelacionado'
        ])
            ->when(!empty($this->idsSeleccionados), fn($q) => $q->whereIn('id', $this->idsSeleccionados))
            ->when(empty($this->idsSeleccionados) && !empty($this->filtros['mandante_id']), fn($q) => $q->where('mandante_id', $this->filtros['mandante_id']))
            ->when(empty($this->idsSeleccionados) && !empty($this->filtros['tipo_entidad_id']), fn($q) => $q->where('tipo_entidad_controlada_id', $this->filtros['tipo_entidad_id']))
            ->when(empty($this->idsSeleccionados) && !empty($this->filtros['nombre_documento']), function ($q) {
            $q->whereHas('nombreDocumento', function ($sq) {
                    $sq->where('nombre', 'like', '%' . $this->filtros['nombre_documento'] . '%');
                }
                );
            })
            ->get();

        $filas = collect();

        $condicionesEmpresaMap = \App\Models\TipoCondicion::pluck('nombre', 'id')->toArray();
        $condicionesPersonaMap = \App\Models\TipoCondicionPersonal::pluck('nombre', 'id')->toArray();

        foreach ($reglas as $regla) {
            $baseRow = [
                $regla->id,
                $regla->mandante->razon_social ?? '',
                $regla->tipoEntidadControlada->nombre_entidad ?? '',
                $regla->nombreDocumento->nombre ?? '',
                $regla->is_active ? 'Activa' : 'Inactiva',
                $regla->valor_nominal_documento,
                $regla->condicionesEmpresaAplica->pluck('nombre')->join(', '),
                $regla->condicionesPersonaAplica->pluck('nombre')->join(', '),
                $regla->condicionFechaIngreso->nombre ?? '',
                $regla->fecha_comparacion_ingreso ? $regla->fecha_comparacion_ingreso->format('d/m/Y') : '',
                $regla->dias_validez_documento,
                $regla->dias_gracia_carga,
                $regla->dias_aviso_vencimiento,
                $regla->tipoVencimiento->nombre ?? '',
                $regla->valida_emision ? 'Sí' : 'No',
                $regla->valida_vencimiento ? 'Sí' : 'No',
                $regla->requiere_validacion_mandante ? 'Sí' : 'No',
                $regla->mostrar_historico_documento ? 'Sí' : 'No',
                $regla->permite_ver_nacionalidad_trabajador ? 'Sí' : 'No',
                $regla->permite_modificar_nacionalidad_trabajador ? 'Sí' : 'No',
                $regla->permite_ver_fecha_nacimiento_trabajador ? 'Sí' : 'No',
                $regla->permite_modificar_fecha_nacimiento_trabajador ? 'Sí' : 'No',
                $regla->observacionDocumento->nombre ?? '',
                $regla->formatoDocumento->nombre ?? '',
                $regla->documentoRelacionado->nombre ?? '',
                $regla->cargosAplica->pluck('nombre_cargo')->join(', '),
                $regla->nacionalidadesAplica->pluck('nombre')->join(', '),
                $regla->tiposPermanenciaAplica->pluck('nombre')->join(', '),
                $regla->unidadesOrganizacionales->pluck('nombre_unidad')->join(', '),
                $regla->tiposVehiculoAplica->pluck('nombre')->join(', '),
                $regla->tiposMaquinariaAplica->pluck('nombre')->join(', '),
                $regla->tiposEmbarcacionAplica->pluck('nombre')->join(', '),
                $regla->tenenciasAplica->pluck('nombre')->join(', '),
                $regla->rut_especificos,
                $regla->rut_excluidos,
                $regla->imc_meses_estimados,
            ];

            if ($regla->criterios->isEmpty()) {
                $row = $baseRow;
                $row[] = ''; // Criterio
                $row[] = ''; // Subcriterio
                $row[] = ''; // Aclaración
                $row[] = ''; // Texto Rechazo
                $row[] = ''; // Fuente
                $row[] = ''; // Condición Trabajador (Subcriterio)
                $row[] = ''; // Condición Empresa (Subcriterio)
                $filas->push($row);
            }
            else {
                foreach ($regla->criterios as $c) {
                    if ($c->subCriterios->isEmpty()) {
                        $row = $baseRow;
                        $row[] = $c->criterioEvaluacion->nombre_criterio ?? '';
                        $row[] = ''; // Subcriterio
                        $row[] = $c->aclaracionCriterio->titulo ?? '';
                        $row[] = $c->textoRechazo->titulo ?? '';
                        $row[] = strtoupper($c->fuente_validacion ?? '');
                        $row[] = ''; // Condición Trabajador (Subcriterio)
                        $row[] = ''; // Condición Empresa (Subcriterio)
                        $filas->push($row);
                    } else {
                        foreach ($c->subCriterios as $sub) {
                            $row = $baseRow;
                            $row[] = $c->criterioEvaluacion->nombre_criterio ?? '';
                            $row[] = $sub->nombre ?? '';
                            $row[] = $c->aclaracionCriterio->titulo ?? '';
                            $row[] = $c->textoRechazo->titulo ?? '';
                            $row[] = strtoupper($c->fuente_validacion ?? '');
                            $row[] = $sub->pivot->tipo_condicion_personal_id ? ($condicionesPersonaMap[$sub->pivot->tipo_condicion_personal_id] ?? '') : '';
                            $row[] = $sub->pivot->tipo_condicion_id ? ($condicionesEmpresaMap[$sub->pivot->tipo_condicion_id] ?? '') : '';
                            $filas->push($row);
                        }
                    }
                }
            }
        }

        return $filas;
    }

    public function title(): string
    {
        return 'Configuración de Reglas';
    }

    public function headings(): array
    {
        return [
            'ID', 'Principal (Mandante)', 'Entidad', 'Nombre Documento', 'Estado',
            'Valor Nominal', 'Condición Empresa', 'Condición Persona', 'Condición Fecha Ingreso', 'Fecha Comparación Ingreso',
            'Días Validez', 'Días Gracia', 'Aviso Vencimiento', 'Tipo Vencimiento',
            'Valida Emisión', 'Valida Vencimiento', 'Requiere Validación Mandante',
            'Mostrar Histórico', 'Permite Ver Nacionalidad', 'Permite Modif. Nacionalidad',
            'Permite Ver F. Nacimiento', 'Permite Modif. F. Nacimiento',
            'Observación Documento', 'Formato Documento', 'Documento Relacionado',
            'Cargos Aplicables', 'Nacionalidades Aplicables', 'Tipos Permanencia Aplicables', 'UOs Aplicables',
            'Vehículos Aplicables', 'Maquinaria Aplicable', 'Embarcaciones Aplicables', 'Tenencias Aplicables',
            'RUTs Específicos', 'RUTs Excluidos', 'Duración Meses IMC',
            'Criterio de Evaluación', 'Subcriterio', 'Aclaración de Criterio', 'Texto de Rechazo', 'Fuente de Validación',
            'Condición Trabajador (Subcriterio)', 'Condición Empresa (Subcriterio)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $mandatoryStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDC2626'], // Tailwind red-600
            ],
            'font' => [
                'color' => ['argb' => Color::COLOR_WHITE],
                'bold' => true,
            ],
        ];

        return [
            1 => ['font' => ['bold' => true]],
            'B1' => $mandatoryStyle, // Principal (Mandante)
            'C1' => $mandatoryStyle, // Entidad
            'D1' => $mandatoryStyle, // Nombre Documento
            'F1' => $mandatoryStyle, // Valor Nominal
            'N1' => $mandatoryStyle, // Tipo Vencimiento
            'AC1' => $mandatoryStyle, // UOs Aplicables
            'AK1' => $mandatoryStyle, // Criterio de Evaluación
            'AN1' => $mandatoryStyle, // Texto de Rechazo
        ];
    }
}

class HistorialSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filtros;
    protected $idsSeleccionados;

    public function __construct($filtros, $idsSeleccionados)
    {
        $this->filtros         = $filtros;
        $this->idsSeleccionados = $idsSeleccionados;
    }

    public function collection()
    {
        $query = Activity::where('subject_type', ReglaDocumental::class);

        if (!empty($this->idsSeleccionados)) {
            $query->whereIn('subject_id', $this->idsSeleccionados);
        }

        $actividades = $query->with('causer')->latest()->get();

        $filas = collect();

        foreach ($actividades as $actividad) {
            $subject = ReglaDocumental::with('nombreDocumento')->find($actividad->subject_id);
            $nombreRegla = $subject
                ? ($subject->nombreDocumento?->nombre ?? "Regla #{$actividad->subject_id}")
                : "Regla eliminada (#{$actividad->subject_id})";

            $base = [
                $actividad->created_at->format('d/m/Y H:i:s'),
                $actividad->subject_id . ' - ' . $nombreRegla,
                $actividad->causer?->name ?? 'Sistema',
                $actividad->description,
            ];

            $props         = $actividad->properties;
            $hayAlgunCambio = false;

            // --- Atributos escalares ---
            if (isset($props['attributes'])) {
                foreach ($props['attributes'] as $key => $valores) {
                    if (is_array($valores) && isset($valores['old'], $valores['new'])) {
                        $old = $valores['old'];
                        $new = $valores['new'];
                    } else {
                        // Soporte formato legacy de Spatie
                        $new = $valores;
                        $old = $props['old'][$key] ?? 'N/A';
                    }
                    
                    // Traducción simple para visualización en Excel
                    $labelKey = $this->obtenerLabelAtributo($key);
                    $diff = $this->calcularDiferenciaSimple($old, $new);

                    $filas->push(array_merge($base, ['ATRIBUTO', $labelKey, $old, $new, $diff]));
                    $hayAlgunCambio = true;
                }
            }

            // --- Relaciones (CARGOS, UNIDADES, TIPOS DE VEHICULO, etc.) ---
            if (isset($props['relations'])) {
                foreach ($props['relations'] as $relName => $relData) {
                    $oldRaw = $relData['old'] ?? 'NINGUNA';
                    $newRaw = $relData['new'] ?? 'NINGUNA';
                    
                    $oldArr = $oldRaw === 'NINGUNA' ? [] : array_map('trim', explode(',', $oldRaw));
                    $newArr = $newRaw === 'NINGUNA' ? [] : array_map('trim', explode(',', $newRaw));
                    
                    $agregados = array_diff($newArr, $oldArr);
                    $eliminados = array_diff($oldArr, $newArr);
                    
                    $diffStrs = [];
                    foreach ($agregados as $add) { if($add) $diffStrs[] = "(+) " . $add; }
                    foreach ($eliminados as $rm) { if($rm) $diffStrs[] = "(-) " . $rm; }
                    
                    $diffFinal = empty($diffStrs) ? 'Sin cambios' : implode(' | ', $diffStrs);

                    $filas->push(array_merge($base, [
                        'RELACIÓN',
                        $relName,
                        $oldRaw,
                        $newRaw,
                        $diffFinal
                    ]));
                    $hayAlgunCambio = true;
                }
            }

            // --- Criterios ASEM / Mandante (Nuevo Formato) ---
            if (isset($props['criterios_originales']) || isset($props['criterios_nuevos'])) {
                $origList = $props['criterios_originales'] ?? [];
                $newList = $props['criterios_nuevos'] ?? [];
                
                $criterioToString = function($c) {
                    $partes = [];
                    if (!empty($c['Criterio Evaluación'])) $partes[] = $c['Criterio Evaluación'];
                    if (!empty($c['Sub-criterio']) && $c['Sub-criterio'] !== 'Ninguno') $partes[] = "> ".$c['Sub-criterio'];
                    if (!empty($c['Aclaración Criterio']) && $c['Aclaración Criterio'] !== 'Ninguna') $partes[] = "(".$c['Aclaración Criterio'].")";
                    return implode(' ', $partes) . " [{$c['Fuente']}]";
                };

                $oldStr = empty($origList) ? 'NINGUNO' : collect($origList)->map($criterioToString)->join(" \n ");
                $newStr = empty($newList) ? 'NINGUNO' : collect($newList)->map($criterioToString)->join(" \n ");

                $diffStrs = [];
                $maxItems = max(count($origList), count($newList));
                
                for ($i = 0; $i < $maxItems; $i++) {
                    $orig = $origList[$i] ?? null;
                    $new = $newList[$i] ?? null;
                    
                    if ($orig && !$new) {
                        $diffStrs[] = "(-) " . $criterioToString($orig);
                    } elseif (!$orig && $new) {
                        $diffStrs[] = "(+) " . $criterioToString($new);
                    } elseif ($orig && $new) {
                        $subDiffs = [];
                        foreach ($orig as $k => $v) {
                            if (($new[$k] ?? null) !== $v) {
                                $subDiffs[] = "$k: '$v' -> '{$new[$k]}'";
                            }
                        }
                        if (!empty($subDiffs)) {
                            $diffStrs[] = "(Criterio " . ($i + 1) . " modificado) " . implode(", ", $subDiffs);
                        }
                    }
                }
                
                $diffFinal = empty($diffStrs) ? 'Sin cambios' : implode(" \n ", $diffStrs);

                $filas->push(array_merge($base, [
                    'CRITERIOS',
                    'Configuración de Criterios',
                    $oldStr,
                    $newStr,
                    $diffFinal
                ]));
                $hayAlgunCambio = true;
            }

            // --- Criterios formato antiguo (Por compatibilidad) ---
            if (isset($props['criterios']) && !isset($props['criterios_originales'])) {
                $oldStr = $props['criterios']['old'] ?? '(NINGUNO)';
                $newStr = $props['criterios']['new'] ?? '(NINGUNO)';
                
                $oldClean = str_replace('||', '|', $oldStr);
                $newClean = str_replace('||', '|', $newStr);
                
                $oldParts = array_filter(array_map('trim', explode('|', $oldClean)));
                $newParts = array_filter(array_map('trim', explode('|', $newClean)));
                
                $agregados = array_diff($newParts, $oldParts);
                $eliminados = array_diff($oldParts, $newParts);
                
                $diffStrs = [];
                foreach ($eliminados as $rm) {
                    if ($rm !== '(NINGUNO)') $diffStrs[] = "(-) " . $rm;
                }
                foreach ($agregados as $add) {
                    if ($add !== '(NINGUNO)') $diffStrs[] = "(+) " . $add;
                }
                
                $diffFinal = empty($diffStrs) ? 'Cambio menor' : implode(" \n ", $diffStrs);
                
                $filas->push(array_merge($base, [
                    'CRITERIOS',
                    'CRITERIOS DE EVALUACION (Legacy)',
                    $oldStr,
                    $newStr,
                    $diffFinal
                ]));
                $hayAlgunCambio = true;
            }

            // Si no hay detalle de cambio (ej: creacion inicial), poner fila genérica
            if (!$hayAlgunCambio) {
                $filas->push(array_merge($base, ['-', '-', '-', '-', '-']));
            }
        }

        return $filas;
    }
    
    private function calcularDiferenciaSimple($old, $new)
    {
        if ($old == $new) return 'Sin cambios';
        return $old . ' -> ' . $new;
    }

    private function obtenerLabelAtributo($key)
    {
        $map = [
            'is_active' => 'Estado Regla',
            'imc_meses_estimados' => 'Duración Meses (IMC)',
            'valida_emision' => 'Valida Emisión',
            'valida_vencimiento' => 'Valida Vencimiento',
            'mostrar_historico_documento' => 'Mostrar Histórico',
            'requiere_validacion_mandante' => 'Req. Val. Mandante',
            'dias_validez_documento' => 'Días Validez',
            'dias_gracia_carga' => 'Días Gracia Carga',
            'dias_aviso_vencimiento' => 'Días Aviso Vencimiento',
            'valor_nominal_documento' => 'Valor Nominal',
            // ... more mappings as needed
        ];
        return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    public function title(): string
    {
        return 'Historial de Cambios';
    }

    public function headings(): array
    {
        return [
            'FECHA',
            'REGLA (ID - DOCUMENTO)',
            'USUARIO',
            'ACCION',
            'TIPO DE CAMBIO',
            'CAMPO / RELACION',
            'VALOR ANTERIOR',
            'VALOR NUEVO',
            'CAMBIOS ESPECÍFICOS',
        ];
    }
}

