<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\ReglaDocumental;
use App\Models\Mandante;
use App\Models\TipoEntidadControlable;
use App\Models\NombreDocumento;
use App\Models\TipoCondicion;
use App\Models\TipoCondicionPersonal;
use App\Models\CargoMandante;
use App\Models\Nacionalidad;
use App\Models\TipoPermanencia;
use App\Models\CondicionFechaIngreso;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ObservacionDocumento;
use App\Models\FormatoDocumentoMuestra;
use App\Models\TipoVencimiento;
use App\Models\CriterioEvaluacion;
use App\Models\SubCriterio;
use App\Models\TextoRechazo;
use App\Models\AclaracionCriterio;
use App\Models\TipoVehiculo;
use App\Models\TipoMaquinaria;
use App\Models\TipoEmbarcacion;
use App\Models\TenenciaVehiculo;
use App\Models\SubTipoVehiculoMandante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ActualizarEstadoCumplimientoEnMasa;
use App\Exports\ReglasDocumentalesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;



class GestionReglasDocumentales extends Component
{
    use WithPagination, WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $isOpen = false;
    public $reglaDocumentalId;
    public $modoEdicion = false;

    // --- Propiedades del formulario ---
    public $mandante_id;
    public $tipo_entidad_controlada_id;
    public $nombre_documento_id;
    public $valor_nominal_documento = 1;
    public array $condicionesEmpresaSeleccionadas = [];
    public array $tiposEmpresaLegalSeleccionados = [];
    public array $condicionesPersonaSeleccionadas = [];
    public array $condicionesVehiculoSeleccionadas = [];
    public $rut_especificos;
    public $rut_excluidos;
    public $rut_contratistas_excluidos;
    public $condicion_fecha_ingreso_id;
    public $fecha_comparacion_ingreso;
    public $observacion_documento_id;
    public $formato_documento_id;
    public $documento_relacionado_id;
    public array $observacionesSeleccionadas = [];
    public array $formatosSeleccionados = [];
    public array $documentosRelacionadosSeleccionados = [];
    public $tipo_vencimiento_id;
    public $dias_validez_documento;
    public $dias_gracia_carga;
    public $imc_meses_estimados;
    public $dias_aviso_vencimiento = 30;
    public $valida_emision = false;
    public $valida_vencimiento = false;
    public $requiere_validacion_mandante = false;
    public $valida_solo_mandante = false;
    public $criteriosMandante = [];
    public $mostrar_historico_documento = false;
    public $permite_ver_nacionalidad_trabajador = false;
    public $permite_modificar_nacionalidad_trabajador = false;
    public $permite_ver_fecha_nacimiento_trabajador = false;
    public $permite_modificar_fecha_nacimiento_trabajador = false;
    public $is_active = true;
    public $criterios = [];
    public $unidadesSeleccionadas = [];
    public $cargosSeleccionados = [];
    public $nacionalidadesSeleccionadas = [];
    public $tiposPermanenciaSeleccionados = [];
    public $tiposVehiculoSeleccionados = [];
    public $tiposMaquinariaSeleccionados = [];
    public $tiposEmbarcacionSeleccionados = [];
    public $tenenciasSeleccionadas = [];
    public array $subTiposVehiculoSeleccionados = [];
    public $listaMandantes;
    public Collection $listaTiposEntidadControlable;
    public $listaNombresDocumento;
    public $listaTiposEmpresaLegal;
    public $listaTiposCondicionEmpresa;
    public $listaTiposCondicionPersonal;
    public $listaTiposCondicionVehiculo;
    public $listaCargosMandante = [];
    public $listaNacionalidades;
    public $listaTiposPermanencia;
    public $listaTiposVehiculo;
    public $listaTiposMaquinaria;
    public $listaTiposEmbarcacion;
    public $listaTenenciasVehiculo;
    public $listaSubTiposVehiculoMandante = [];
    public $listaCondicionesFechaIngreso;
    public $listaObservacionesDocumento;
    public $listaFormatosDocumentoMuestra;
    public $listaTiposVencimiento;
    public $listaCriteriosEvaluacion;
    public $listaSubCriterios;
    public $listaTextosRechazo;
    public $listaAclaracionesCriterio;
    public $showHistoryModal = false;
    public $nombreReglaHistorial = '';
    public $historialActividad = [];
    public $reglaParaHistorial;
    public $nombreEntidadSeleccionada;
    public $showConfirmDeleteModal = false;
    public $reglaIdParaEliminar;
    public $nombreReglaParaEliminar;
    public $importResults = [];

    // Propiedades para Reporte IMC
    public $showReporteImcModal = false;
    public $mandantesSeleccionadosParaImc = [];
    public $imcSoloActivas = true;
    public $imcTotalPrincipal = 0;

    // Propiedades de Filtrado y Ordenamiento
    public $filtroMandanteId = '';
    public $filtroTipoEntidadId = '';
    public $filtroNombreDocumento = '';
    public $sortBy = 'reglas_documentales.id';
    public $sortDirection = 'desc';

    // Propiedades para Exportación Avanzada
    public $showExportOptionsModal = false;
    public $exportSelectedOnly = false;
    public $exportIncludeHistory = false;
    public $reglasSeleccionadas = [];
    public $seleccionarTodas = false;

    // Propiedades para Importación Masiva
    public $showImportModal = false;
    public $archivoImport;

    protected function rules()
    {
        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        $idsTiposVencimientoQueRequierenDias = [];
        if (isset($this->listaTiposVencimiento) && $this->listaTiposVencimiento instanceof Collection && !$this->listaTiposVencimiento->isEmpty()) {
            $idsTiposVencimientoQueRequierenDias = $this->listaTiposVencimiento
                ->whereIn('nombre', $nombresTiposVencimientoQueRequierenDias)
                ->pluck('id')
                ->toArray();
        }

        $rules = [
            'mandante_id' => 'required|exists:mandantes,id',
            'tipo_entidad_controlada_id' => 'required|exists:tipos_entidad_controlable,id',
            'nombre_documento_id' => 'required|exists:nombre_documentos,id',
            'valor_nominal_documento' => 'required|numeric|min:0',
            'condicionesEmpresaSeleccionadas' => 'nullable|array',
            'condicionesEmpresaSeleccionadas.*' => 'integer|exists:tipos_condicion,id',
            'tiposEmpresaLegalSeleccionados' => 'nullable|array',
            'tiposEmpresaLegalSeleccionados.*' => 'integer|exists:tipos_empresa_legal,id',
            'condicionesVehiculoSeleccionadas' => 'nullable|array',
            'condicionesVehiculoSeleccionadas.*' => 'integer|exists:tipos_condicion_vehiculo,id',
            'rut_especificos' => 'nullable|string',
            'rut_excluidos' => 'nullable|string',
            'rut_contratistas_excluidos' => 'nullable|string',
            'observacionesSeleccionadas' => 'nullable|array',
            'observacionesSeleccionadas.*' => 'integer|exists:observaciones_documento,id',
            'formatosSeleccionados' => 'nullable|array',
            'formatosSeleccionados.*' => 'integer|exists:formatos_documento_muestra,id',
            'documentosRelacionadosSeleccionados' => 'nullable|array',
            'documentosRelacionadosSeleccionados.*' => 'integer|exists:nombre_documentos,id',
            'tipo_vencimiento_id' => 'required|exists:tipos_vencimiento,id',
            'dias_validez_documento' => 'nullable|integer|min:0',
            'dias_gracia_carga' => 'nullable|integer|min:0',
            'imc_meses_estimados' => 'nullable|numeric|min:0',
            'dias_aviso_vencimiento' => 'nullable|integer|min:0',
            'valida_emision' => 'boolean',
            'valida_vencimiento' => 'boolean',
            'requiere_validacion_mandante' => 'boolean',
            'valida_solo_mandante' => 'boolean',
            'mostrar_historico_documento' => 'boolean',
            'is_active' => 'boolean',
            'criterios' => 'array',
            'criterios.*.criterio_evaluacion_id' => 'required|exists:criterios_evaluacion,id',
            // sub_criterios_config: array de objetos {sub_criterio_id, cond_personal_id, cond_empresa_id}
            'criterios.*.sub_criterios_config' => 'nullable|array',
            'criterios.*.sub_criterios_config.*.sub_criterio_id' => 'required|exists:sub_criterios,id',
            'criterios.*.sub_criterios_config.*.cond_personal_id' => 'nullable|exists:tipos_condicion_personal,id',
            'criterios.*.sub_criterios_config.*.cond_empresa_id' => 'nullable|exists:tipos_condicion,id',
            'criterios.*.texto_rechazo_id' => 'nullable|exists:textos_rechazo,id',
            'criterios.*.aclaracion_criterio_id' => 'nullable|exists:aclaraciones_criterio,id',
            'criteriosMandante' => 'required_if:requiere_validacion_mandante,true|array',
            'criteriosMandante.*.criterio_evaluacion_id' => 'required_if:requiere_validacion_mandante,true|exists:criterios_evaluacion,id',
            'criteriosMandante.*.sub_criterios_config' => 'nullable|array',
            'criteriosMandante.*.sub_criterios_config.*.sub_criterio_id' => 'required|exists:sub_criterios,id',
            'criteriosMandante.*.sub_criterios_config.*.cond_personal_id' => 'nullable|exists:tipos_condicion_personal,id',
            'criteriosMandante.*.sub_criterios_config.*.cond_empresa_id' => 'nullable|exists:tipos_condicion,id',
            'criteriosMandante.*.texto_rechazo_id' => 'nullable|exists:textos_rechazo,id',
            'criteriosMandante.*.aclaracion_criterio_id' => 'nullable|exists:aclaraciones_criterio,id',
            'unidadesSeleccionadas' => 'array|min:1', 
            'unidadesSeleccionadas.*.final_uo_id' => 'required|exists:unidades_organizacionales_mandante,id',
        ];

        $entidad = $this->getNombreEntidadSeleccionada();

        if ($entidad === 'PERSONA') {
            $rules['condicionesPersonaSeleccionadas'] = 'nullable|array';
            $rules['condicionesPersonaSeleccionadas.*'] = 'integer|exists:tipos_condicion_personal,id';
            $rules['cargosSeleccionados'] = 'nullable|array'; 
            $rules['cargosSeleccionados.*'] = 'integer|exists:cargos_mandante,id'; 
            $rules['nacionalidadesSeleccionadas'] = 'nullable|array'; 
            $rules['nacionalidadesSeleccionadas.*'] = 'integer|exists:nacionalidades,id';
            $rules['tiposPermanenciaSeleccionados'] = 'nullable|array';
            $rules['tiposPermanenciaSeleccionados.*'] = 'integer|exists:tipos_permanencias,id';
            $rules['permite_ver_nacionalidad_trabajador'] = 'boolean';
            $rules['permite_modificar_nacionalidad_trabajador'] = 'boolean';
            $rules['permite_ver_fecha_nacimiento_trabajador'] = 'boolean';
            $rules['permite_modificar_fecha_nacimiento_trabajador'] = 'boolean';
            $rules['condicion_fecha_ingreso_id'] = 'nullable|exists:condiciones_fecha_ingreso,id';
            $rules['fecha_comparacion_ingreso'] = 'nullable|date|required_with:condicion_fecha_ingreso_id';
        } elseif ($entidad === 'VEHICULO') {
            $rules['tiposVehiculoSeleccionados'] = 'nullable|array';
            $rules['tiposVehiculoSeleccionados.*'] = 'integer|exists:tipos_vehiculo,id';
        } elseif ($entidad === 'MAQUINARIA') {
            $rules['tiposMaquinariaSeleccionados'] = 'nullable|array';
            $rules['tiposMaquinariaSeleccionados.*'] = 'integer|exists:tipos_maquinaria,id';
        } elseif ($entidad === 'EMBARCACION') {
            $rules['tiposEmbarcacionSeleccionados'] = 'nullable|array';
            $rules['tiposEmbarcacionSeleccionados.*'] = 'integer|exists:tipos_embarcacion,id';
        }
        
        if (in_array($entidad, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'])) {
            $rules['tenenciasSeleccionadas'] = 'nullable|array';
            $rules['tenenciasSeleccionadas.*'] = 'integer|exists:tenencias_vehiculo,id';
        }

        if ($entidad === 'VEHICULO') {
            $rules['subTiposVehiculoSeleccionados'] = 'nullable|array';
            $rules['subTiposVehiculoSeleccionados.*'] = 'integer|exists:sub_tipos_vehiculo_mandante,id';
        }

        return $rules;
    }

    protected $validationAttributes = [
        'mandante_id' => 'Mandante',
        'tipo_entidad_controlada_id' => 'Entidad Controlada',
        'nombre_documento_id' => 'Documento',
        'tiposEmpresaLegalSeleccionados' => 'Tipos de Empresa Legal Aplicables',
        'tiposEmpresaLegalSeleccionados.*' => 'Tipo de Empresa Legal Aplicable',
        'aplica_empresa_condicion_id' => 'Condición Empresa',
        'aplica_persona_condicion_id' => 'Condición Persona',
        'condicionesVehiculoSeleccionadas' => 'Condiciones de Vehículo Aplicables',
        'condicionesVehiculoSeleccionadas.*' => 'Condición de Vehículo Aplicable',
        'cargosSeleccionados' => 'Cargos Aplicables',
        'cargosSeleccionados.*' => 'Cargo Aplicable',
        'nacionalidadesSeleccionadas' => 'Nacionalidades Aplicables',
        'nacionalidadesSeleccionadas.*' => 'Nacionalidad Aplicable',
        'tiposPermanenciaSeleccionados' => 'Tipos de Permanencia Aplicables',
        'tiposPermanenciaSeleccionados.*' => 'Tipo de Permanencia Aplicable',
        'tiposVehiculoSeleccionados' => 'Tipos de Vehículo Aplicables',
        'tiposVehiculoSeleccionados.*' => 'Tipo de Vehículo Aplicable',
        'tiposMaquinariaSeleccionados' => 'Tipos de Maquinaria Aplicables',
        'tiposMaquinariaSeleccionados.*' => 'Tipo de Maquinaria Aplicable',
        'tiposEmbarcacionSeleccionados' => 'Tipos de Embarcación Aplicables',
        'tiposEmbarcacionSeleccionados.*' => 'Tipo de Embarcación Aplicable',
        'tenenciasSeleccionadas' => 'Tenencias de Activo Aplicables',
        'tenenciasSeleccionadas.*' => 'Tenencia de Activo Aplicable',
        'subTiposVehiculoSeleccionados' => 'Sub-Tipos de Vehículo Aplicables',
        'subTiposVehiculoSeleccionados.*' => 'Sub-Tipo de Vehículo Aplicable',
        'rut_especificos' => 'Identificadores Específicos',
        'rut_excluidos' => 'Identificadores Excluidos',
        'condicion_fecha_ingreso_id' => 'Opción Fechas Ingreso',
        'fecha_comparacion_ingreso' => 'Fecha de Comparación',
        'observacionesSeleccionadas' => 'Observaciones Documento',
        'formatosSeleccionados' => 'Formatos Documento',
        'documentosRelacionadosSeleccionados' => 'Documentos Relacionados',
        'tipo_vencimiento_id' => 'Tipo de Vencimiento',
        'dias_validez_documento' => 'Días Validez Documento',
        'dias_gracia_carga' => 'Días de Gracia para Carga',
        'requiere_validacion_mandante' => 'Requiere Validación de Mandante',
        'criterios.*.criterio_evaluacion_id' => 'Criterio de Evaluación (ASEM - fila :index)',
        'criteriosMandante' => 'Criterios de Evaluación del Mandante',
        'criteriosMandante.*.criterio_evaluacion_id' => 'Criterio de Evaluación (Mandante - fila :index)',
        'unidadesSeleccionadas.*.final_uo_id' => 'Unidad Organizacional seleccionada (fila :index)',
    ];

    public function mount()
    {
        $this->cargarListadosUniversales();
        $this->actualizarNombreEntidadSeleccionada(); 
    }

    public function cargarListadosUniversales()
    {
        $this->listaMandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->listaTiposEntidadControlable = TipoEntidadControlable::where('is_active', true)->orderBy('nombre_entidad')->get();
        $this->listaTiposEmpresaLegal = \App\Models\TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->get();
        $this->listaNombresDocumento = NombreDocumento::where('is_active', true)->orderBy('nombre')->get();

        // Si ya hay un mandante seleccionado (modo edición), cargar sus condiciones.
        // De lo contrario, dejar vacío para que el usuario seleccione el mandante.
        if ($this->mandante_id) {
            $this->listaTiposCondicionEmpresa = TipoCondicion::where('is_active', true)
                ->where(function($q) {
                    $q->where('mandante_id', $this->mandante_id)
                      ->orWhereNull('mandante_id');
                })
                ->orderBy('nombre')
                ->get();

            $this->listaTiposCondicionPersonal = TipoCondicionPersonal::where('is_active', true)
                ->where(function($q) {
                    $q->where('mandante_id', $this->mandante_id)
                      ->orWhereNull('mandante_id');
                })
                ->orderBy('nombre')
                ->get();
        } else {
            $this->listaTiposCondicionEmpresa = collect();
            $this->listaTiposCondicionPersonal = collect();
        }

        $this->listaTiposCondicionVehiculo = \App\Models\TipoCondicionVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->listaNacionalidades = Nacionalidad::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposPermanencia = TipoPermanencia::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposVehiculo = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposMaquinaria = TipoMaquinaria::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposEmbarcacion = TipoEmbarcacion::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTenenciasVehiculo = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->listaCondicionesFechaIngreso = CondicionFechaIngreso::where('is_active', true)->orderBy('nombre')->get();
        $this->listaObservacionesDocumento = ObservacionDocumento::where('is_active', true)->orderBy('titulo')->get();
        $this->listaFormatosDocumentoMuestra = FormatoDocumentoMuestra::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposVencimiento = TipoVencimiento::where('is_active', true)->orderBy('nombre')->get();
        $this->listaCriteriosEvaluacion = CriterioEvaluacion::where('is_active', true)->orderBy('nombre_criterio')->get();
        $this->listaSubCriterios = SubCriterio::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTextosRechazo = TextoRechazo::where('is_active', true)->orderBy('titulo')->get();
        $this->listaAclaracionesCriterio = AclaracionCriterio::where('is_active', true)->orderBy('titulo')->get();
    }

    
    public function updatedTipoEntidadControladaId($value)
    {
        $this->condicionesPersonaSeleccionadas = [];
        $this->tiposEmpresaLegalSeleccionados = [];
        $this->cargosSeleccionados = [];
        $this->nacionalidadesSeleccionadas = [];
        $this->tiposPermanenciaSeleccionados = [];
        $this->tiposVehiculoSeleccionados = [];
        $this->subTiposVehiculoSeleccionados = [];
        $this->condicionesVehiculoSeleccionadas = [];
        $this->tiposMaquinariaSeleccionados = [];
        $this->tiposEmbarcacionSeleccionados = [];
        $this->tenenciasSeleccionadas = [];
        $this->condicion_fecha_ingreso_id = null;
        $this->fecha_comparacion_ingreso = null;
        $this->permite_ver_nacionalidad_trabajador = false;
        $this->permite_modificar_nacionalidad_trabajador = false;
        $this->permite_ver_fecha_nacimiento_trabajador = false;
        $this->permite_modificar_fecha_nacimiento_trabajador = false;
        $this->actualizarNombreEntidadSeleccionada();
    }

    private function actualizarNombreEntidadSeleccionada()
    {
        if ($this->tipo_entidad_controlada_id && isset($this->listaTiposEntidadControlable) && $this->listaTiposEntidadControlable instanceof Collection) {
            $entidad = $this->listaTiposEntidadControlable->firstWhere('id', $this->tipo_entidad_controlada_id);
            $this->nombreEntidadSeleccionada = $entidad ? strtoupper($entidad->nombre_entidad) : null;
        } else {
            $this->nombreEntidadSeleccionada = null;
        }
    }

    public function getNombreEntidadSeleccionada() : ?string
    {
        if (!$this->nombreEntidadSeleccionada && $this->tipo_entidad_controlada_id) {
            $this->actualizarNombreEntidadSeleccionada();
        }
        return $this->nombreEntidadSeleccionada;
    }

    public function updatedFiltroMandanteId(){ $this->resetPage(); }
    public function updatedFiltroTipoEntidadId(){ $this->resetPage(); }
    public function updatedFiltroNombreDocumento(){ $this->resetPage(); }
    public function resetFilters(){ $this->filtroMandanteId = ''; $this->filtroTipoEntidadId = ''; $this->filtroNombreDocumento = ''; $this->sortBy = 'reglas_documentales.id'; $this->sortDirection = 'desc'; $this->resetPage(); }
    public function sortBy($field){ if ($this->sortBy === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortBy = $field; $this->resetPage(); }

    public function updatedMandanteId($value)
    {
        // Carga SOLO las condiciones del mandante seleccionado y las universales (filtrado estricto)
        $this->listaTiposCondicionEmpresa = $value
            ? TipoCondicion::where('is_active', true)
                ->where(function($q) use ($value) {
                    $q->where('mandante_id', $value)
                      ->orWhereNull('mandante_id');
                })
                ->orderBy('nombre')
                ->get()
            : collect();

        // Si alguna condición ya seleccionada no pertenece al nuevo mandante, la limpiamos
        if ($value && $this->listaTiposCondicionEmpresa->isNotEmpty()) {
            $idsValidos = $this->listaTiposCondicionEmpresa->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->condicionesEmpresaSeleccionadas = array_values(
                array_filter($this->condicionesEmpresaSeleccionadas, fn($id) => in_array((string)$id, $idsValidos))
            );
        } else {
            $this->condicionesEmpresaSeleccionadas = [];
        }

        $this->listaTiposCondicionPersonal = $value
            ? TipoCondicionPersonal::where('is_active', true)
                ->where(function($q) use ($value) {
                    $q->where('mandante_id', $value)
                      ->orWhereNull('mandante_id');
                })
                ->orderBy('nombre')
                ->get()
            : collect();

        if ($value && $this->listaTiposCondicionPersonal->isNotEmpty()) {
            $idsValidos = $this->listaTiposCondicionPersonal->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->condicionesPersonaSeleccionadas = array_values(
                array_filter($this->condicionesPersonaSeleccionadas, fn($id) => in_array((string)$id, $idsValidos))
            );
        } else {
            $this->condicionesPersonaSeleccionadas = [];
        }

        $this->listaCargosMandante = $value ? CargoMandante::where('mandante_id', $value)->where('is_active', true)->orderBy('nombre_cargo')->get() : [];
        $this->cargosSeleccionados = [];
        $this->listaSubTiposVehiculoMandante = $value ? SubTipoVehiculoMandante::where('mandante_id', $value)->where('is_active', true)->orderBy('nombre')->get() : [];
        $this->subTiposVehiculoSeleccionados = [];
        if (!empty($this->unidadesSeleccionadas)) {
            foreach (array_keys($this->unidadesSeleccionadas) as $index) {
                $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null;
                $this->unidadesSeleccionadas[$index]['final_uo_id'] = null;
            }
        }
    }

    public function updatedTipoVencimientoId($value)
    {
        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        $tipoSeleccionado = null;
        if (isset($this->listaTiposVencimiento) && $this->listaTiposVencimiento instanceof Collection && $value) {
             $tipoSeleccionado = $this->listaTiposVencimiento->firstWhere('id', $value);
        }

        if ($tipoSeleccionado && !in_array(strtoupper($tipoSeleccionado->nombre), $nombresTiposVencimientoQueRequierenDias)) {
            $this->dias_validez_documento = null;
        }
        if (empty($value)) {
             $this->dias_validez_documento = null;
        }
        
        if (!$tipoSeleccionado || strtoupper($tipoSeleccionado->nombre) !== 'POR PERIODO') {
            $this->dias_gracia_carga = null;
        }
        $nombresManuales = ['SEGUN DOCUMENTO', 'INDEFINIDA', 'NO APLICA', 'DESDE CARGA', 'DESDE EMISION'];
        if (!$tipoSeleccionado || (strtoupper($tipoSeleccionado->nombre) !== 'POR PERIODO' && !in_array(strtoupper($tipoSeleccionado->nombre), $nombresManuales))) {
            $this->imc_meses_estimados = null;
        }
    }

    public function getNivel1Options($index) { if (!$this->mandante_id) { return []; } return UnidadOrganizacionalMandante::where('mandante_id', $this->mandante_id)->whereNull('parent_id')->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel2Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel3Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel4Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function uoNivel1Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: null; }
    public function uoNivel2Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel1_id'] ?? null); }
    public function uoNivel3Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel2_id'] ?? null); }
    public function uoNivel4Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel3_id'] ?? null); }
    public function agregarUnidadSeleccionada() { $this->unidadesSeleccionadas[] = [ 'uo_nivel1_id' => null, 'uo_nivel2_id' => null, 'uo_nivel3_id' => null, 'uo_nivel4_id' => null, 'final_uo_id'  => null, ]; }
    public function eliminarUnidadSeleccionada($index) { unset($this->unidadesSeleccionadas[$index]); $this->unidadesSeleccionadas = array_values($this->unidadesSeleccionadas); if (empty($this->unidadesSeleccionadas)) { $this->agregarUnidadSeleccionada(); } }
    
    public function agregarCriterio() 
    {
        $this->criterios[] = [
            'temp_id'               => Str::random(5), 
            'criterio_evaluacion_id' => '', 
            'sub_criterios_config'  => [], // [{sub_criterio_id, cond_personal_id, cond_empresa_id}]
            'texto_rechazo_id'      => '', 
            'aclaracion_criterio_id' => ''
        ]; 
    }

    /** Agrega una fila EPP vacía a un criterio (UI dinámica) */
    public function agregarSubCriterioACriterio(int $criterioIndex)
    {
        $this->criterios[$criterioIndex]['sub_criterios_config'][] = [
            'sub_criterio_id'  => '',
            'cond_personal_id' => null,
            'cond_empresa_id'  => null,
        ];
    }

    /** Elimina una fila EPP de un criterio */
    public function eliminarSubCriterioACriterio(int $criterioIndex, int $scIndex)
    {
        unset($this->criterios[$criterioIndex]['sub_criterios_config'][$scIndex]);
        $this->criterios[$criterioIndex]['sub_criterios_config'] = array_values(
            $this->criterios[$criterioIndex]['sub_criterios_config']
        );
    }
    
    public function eliminarCriterio($key) 
    {
        $criteriosCollection = collect($this->criterios);
        $this->criterios = $criteriosCollection->where('temp_id', '!=', $key)->values()->toArray();
        if (empty($this->criterios)) {
            $this->agregarCriterio();
        }
    }
    
    public function quitarSeleccionDeCargos() { $this->cargosSeleccionados = []; }
    public function seleccionarTodosLosCargos() { if (isset($this->listaCargosMandante) && $this->listaCargosMandante->isNotEmpty()) { $this->cargosSeleccionados = $this->listaCargosMandante->pluck('id')->toArray(); } else { $this->cargosSeleccionados = []; } }
    public function quitarSeleccionDeNacionalidades() { $this->nacionalidadesSeleccionadas = []; }
    public function seleccionarTodasLasNacionalidades() { if (isset($this->listaNacionalidades) && $this->listaNacionalidades->isNotEmpty()) { $this->nacionalidadesSeleccionadas = $this->listaNacionalidades->pluck('id')->toArray(); } else { $this->nacionalidadesSeleccionadas = []; } }
    public function quitarSeleccionDeTiposPermanencia() { $this->tiposPermanenciaSeleccionados = []; }
    public function seleccionarTodosLosTiposPermanencia() { if (isset($this->listaTiposPermanencia) && $this->listaTiposPermanencia->isNotEmpty()) { $this->tiposPermanenciaSeleccionados = $this->listaTiposPermanencia->pluck('id')->toArray(); } else { $this->tiposPermanenciaSeleccionados = []; } }
    public function quitarSeleccionDeTiposVehiculo() { $this->tiposVehiculoSeleccionados = []; }
    public function seleccionarTodosLosTiposVehiculo() { if (isset($this->listaTiposVehiculo) && $this->listaTiposVehiculo->isNotEmpty()) { $this->tiposVehiculoSeleccionados = $this->listaTiposVehiculo->pluck('id')->toArray(); } else { $this->tiposVehiculoSeleccionados = []; } }
    public function quitarSeleccionDeSubTiposVehiculo() { $this->subTiposVehiculoSeleccionados = []; }
    public function seleccionarTodosLosSubTiposVehiculo() { if (!empty($this->listaSubTiposVehiculoMandante) && (is_object($this->listaSubTiposVehiculoMandante) ? $this->listaSubTiposVehiculoMandante->isNotEmpty() : count($this->listaSubTiposVehiculoMandante) > 0)) { $this->subTiposVehiculoSeleccionados = is_object($this->listaSubTiposVehiculoMandante) ? $this->listaSubTiposVehiculoMandante->pluck('id')->toArray() : array_column(is_array($this->listaSubTiposVehiculoMandante) ? $this->listaSubTiposVehiculoMandante : $this->listaSubTiposVehiculoMandante->toArray(), 'id'); } else { $this->subTiposVehiculoSeleccionados = []; } }
    public function quitarSeleccionDeTiposMaquinaria() { $this->tiposMaquinariaSeleccionados = []; }
    public function seleccionarTodosLosTiposMaquinaria() { if (isset($this->listaTiposMaquinaria) && $this->listaTiposMaquinaria->isNotEmpty()) { $this->tiposMaquinariaSeleccionados = $this->listaTiposMaquinaria->pluck('id')->toArray(); } else { $this->tiposMaquinariaSeleccionados = []; } }
    public function quitarSeleccionDeTiposEmbarcacion() { $this->tiposEmbarcacionSeleccionados = []; }
    public function seleccionarTodosLosTiposEmbarcacion() { if (isset($this->listaTiposEmbarcacion) && $this->listaTiposEmbarcacion->isNotEmpty()) { $this->tiposEmbarcacionSeleccionados = $this->listaTiposEmbarcacion->pluck('id')->toArray(); } else { $this->tiposEmbarcacionSeleccionados = []; } }
    public function quitarSeleccionDeTenencias() { $this->tenenciasSeleccionadas = []; }
    public function seleccionarTodasLasTenencias() { if (isset($this->listaTenenciasVehiculo) && $this->listaTenenciasVehiculo->isNotEmpty()) { $this->tenenciasSeleccionadas = $this->listaTenenciasVehiculo->pluck('id')->toArray(); } else { $this->tenenciasSeleccionadas = []; } }
    
    public function quitarSeleccionDeCondicionesEmpresa() { $this->condicionesEmpresaSeleccionadas = []; }
    public function seleccionarTodasLasCondicionesEmpresa() { if (isset($this->listaTiposCondicionEmpresa) && $this->listaTiposCondicionEmpresa->isNotEmpty()) { $this->condicionesEmpresaSeleccionadas = $this->listaTiposCondicionEmpresa->pluck('id')->toArray(); } else { $this->condicionesEmpresaSeleccionadas = []; } }
    public function quitarSeleccionDeTiposEmpresaLegal() { $this->tiposEmpresaLegalSeleccionados = []; }
    public function seleccionarTodosLosTiposEmpresaLegal() { if (isset($this->listaTiposEmpresaLegal) && $this->listaTiposEmpresaLegal->isNotEmpty()) { $this->tiposEmpresaLegalSeleccionados = $this->listaTiposEmpresaLegal->pluck('id')->toArray(); } else { $this->tiposEmpresaLegalSeleccionados = []; } }
    public function quitarSeleccionDeCondicionesPersona() { $this->condicionesPersonaSeleccionadas = []; }
    public function seleccionarTodasLasCondicionesPersona() { if (isset($this->listaTiposCondicionPersonal) && $this->listaTiposCondicionPersonal->isNotEmpty()) { $this->condicionesPersonaSeleccionadas = $this->listaTiposCondicionPersonal->pluck('id')->toArray(); } else { $this->condicionesPersonaSeleccionadas = []; } }
    public function quitarSeleccionDeCondicionesVehiculo() { $this->condicionesVehiculoSeleccionadas = []; }
    public function seleccionarTodasLasCondicionesVehiculo() { if (isset($this->listaTiposCondicionVehiculo) && $this->listaTiposCondicionVehiculo->isNotEmpty()) { $this->condicionesVehiculoSeleccionadas = $this->listaTiposCondicionVehiculo->pluck('id')->toArray(); } else { $this->condicionesVehiculoSeleccionadas = []; } }

    public function quitarSeleccionDeObservaciones() { $this->observacionesSeleccionadas = []; }
    public function quitarSeleccionDeFormatos() { $this->formatosSeleccionados = []; }
    public function quitarSeleccionDeDocRelacionados() { $this->documentosRelacionadosSeleccionados = []; }

    public function agregarCriterioMandante()
    {
        $this->criteriosMandante[] = [
            'temp_id'               => Str::random(5),
            'criterio_evaluacion_id' => '',
            'sub_criterios_config'  => [],
            'texto_rechazo_id'      => '',
            'aclaracion_criterio_id' => ''
        ];
    }

    /** Agrega una fila EPP vacía a un criterio mandante */
    public function agregarSubCriterioACriterioMandante(int $criterioIndex)
    {
        $this->criteriosMandante[$criterioIndex]['sub_criterios_config'][] = [
            'sub_criterio_id'  => '',
            'cond_personal_id' => null,
            'cond_empresa_id'  => null,
        ];
    }

    /** Elimina una fila EPP de un criterio mandante */
    public function eliminarSubCriterioACriterioMandante(int $criterioIndex, int $scIndex)
    {
        unset($this->criteriosMandante[$criterioIndex]['sub_criterios_config'][$scIndex]);
        $this->criteriosMandante[$criterioIndex]['sub_criterios_config'] = array_values(
            $this->criteriosMandante[$criterioIndex]['sub_criterios_config']
        );
    }

    public function eliminarCriterioMandante($key)
    {
        $criteriosCollection = collect($this->criteriosMandante);
        $this->criteriosMandante = $criteriosCollection->where('temp_id', '!=', $key)->values()->toArray();
        if (empty($this->criteriosMandante)) {
            $this->agregarCriterioMandante();
        }
    }
    
    public function create() { $this->resetInputFields(); $this->modoEdicion = false; $this->agregarUnidadSeleccionada(); $this->agregarCriterio(); $this->openModal(); }
    public function openModal() { $this->cargarListadosUniversales(); $this->isOpen = true; $this->actualizarNombreEntidadSeleccionada(); }
    public function closeModal() { $this->isOpen = false; $this->resetErrorBag(); }
    
    private function resetInputFields() {
        $this->reglaDocumentalId = null; $this->mandante_id = null; $this->tipo_entidad_controlada_id = null; $this->nombre_documento_id = null;
        $this->valor_nominal_documento = 1; $this->condicionesEmpresaSeleccionadas = []; $this->condicionesPersonaSeleccionadas = [];
        $this->tiposEmpresaLegalSeleccionados = [];
        $this->condicionesVehiculoSeleccionadas = [];
        $this->cargosSeleccionados = []; $this->nacionalidadesSeleccionadas = []; $this->tiposPermanenciaSeleccionados = []; $this->tiposVehiculoSeleccionados = [];
        $this->subTiposVehiculoSeleccionados = []; $this->tiposMaquinariaSeleccionados = []; $this->tiposEmbarcacionSeleccionados = []; $this->tenenciasSeleccionadas = [];
        $this->rut_especificos = null; $this->rut_excluidos = null; $this->rut_contratistas_excluidos = null; $this->condicion_fecha_ingreso_id = null;
        $this->fecha_comparacion_ingreso = null; $this->observacion_documento_id = null; $this->formato_documento_id = null;
        $this->documento_relacionado_id = null; $this->tipo_vencimiento_id = null; $this->dias_validez_documento = null;
        $this->observacionesSeleccionadas = []; $this->formatosSeleccionados = []; $this->documentosRelacionadosSeleccionados = [];
        $this->dias_gracia_carga = null;
        $this->imc_meses_estimados = null;
        $this->dias_aviso_vencimiento = 30; $this->valida_emision = false; $this->valida_vencimiento = false;
        $this->requiere_validacion_mandante = false;
        $this->valida_solo_mandante = false;
        $this->criteriosMandante = [];
        $this->mostrar_historico_documento = false;
        $this->permite_ver_nacionalidad_trabajador = false; $this->permite_modificar_nacionalidad_trabajador = false;
        $this->permite_ver_fecha_nacimiento_trabajador = false; $this->permite_modificar_fecha_nacimiento_trabajador = false;
        $this->is_active = true; $this->criterios = []; $this->unidadesSeleccionadas = []; $this->listaCargosMandante = [];
        $this->modoEdicion = false; $this->reglaIdParaEliminar = null; $this->nombreReglaParaEliminar = null;
        $this->showConfirmDeleteModal = false; $this->actualizarNombreEntidadSeleccionada(); $this->resetErrorBag();
    }

    private function prepararDatosParaDB(array $data): array
    {
        $camposFKOpcionales = [
            'condicion_fecha_ingreso_id', 'observacion_documento_id',
            'formato_documento_id', 'documento_relacionado_id', 'tipo_vencimiento_id',
            'dias_validez_documento', 'dias_gracia_carga', 'imc_meses_estimados'
        ];

        foreach ($camposFKOpcionales as $campo) {
            if (isset($data[$campo]) && ($data[$campo] === '' || $data[$campo] === null)) {
                $data[$campo] = null;
            }
        }
        
        $entidad = $this->getNombreEntidadSeleccionada();

        if ($entidad !== 'PERSONA') {
            $data['condicion_fecha_ingreso_id'] = null;
            $data['fecha_comparacion_ingreso'] = null;
        }

        if (empty($data['condicion_fecha_ingreso_id'])) {
            $data['fecha_comparacion_ingreso'] = null;
        }

        $tipoVencimientoSeleccionado = null;
        if (isset($this->listaTiposVencimiento) && $this->listaTiposVencimiento instanceof Collection && !empty($data['tipo_vencimiento_id'])) {
             $tipoVencimientoSeleccionado = $this->listaTiposVencimiento->firstWhere('id', $data['tipo_vencimiento_id']);
        }

        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        if (!$tipoVencimientoSeleccionado || !in_array(strtoupper($tipoVencimientoSeleccionado->nombre), $nombresTiposVencimientoQueRequierenDias)) {
            $data['dias_validez_documento'] = null;
        }
        
        if (!$tipoVencimientoSeleccionado || strtoupper($tipoVencimientoSeleccionado->nombre) !== 'POR PERIODO') {
            $data['dias_gracia_carga'] = null;
        }
        
        if ($entidad !== 'PERSONA') {
            $data['permite_ver_nacionalidad_trabajador'] = false;
            $data['permite_modificar_nacionalidad_trabajador'] = false;
            $data['permite_ver_fecha_nacimiento_trabajador'] = false;
            $data['permite_modificar_fecha_nacimiento_trabajador'] = false;
        }

        return $data;
    }

    public function edit($id) {
        $this->resetInputFields(); 
        $regla = ReglaDocumental::with([
            'unidadesOrganizacionales', 'criteriosAsem', 'criteriosMandante', 'cargosAplica', 'nacionalidadesAplica', 'tiposPermanenciaAplica',
            'tiposVehiculoAplica', 'tiposMaquinariaAplica', 'tiposEmbarcacionAplica', 'tenenciasAplica',
            'condicionesEmpresaAplica', 'tiposEmpresaLegalAplica', 'condicionesPersonaAplica', 'condicionesVehiculoAplica',
            'observacionesDocumento', 'formatosDocumento', 'documentosRelacionados'
        ])->find($id); 

        if ($regla) {
            $this->reglaDocumentalId = $regla->id;
            $this->mandante_id = $regla->mandante_id;
            $this->tipo_entidad_controlada_id = $regla->tipo_entidad_controlada_id;
            $this->nombre_documento_id = $regla->nombre_documento_id;
            $this->valor_nominal_documento = $regla->valor_nominal_documento;
            // condicionesSeleccionadas se asignan DESPUÉS de updatedMandanteId() para no ser borradas
            $this->rut_especificos = $regla->rut_especificos;
            $this->rut_excluidos = $regla->rut_excluidos;
            $this->rut_contratistas_excluidos = $regla->rut_contratistas_excluidos;
            $this->observacion_documento_id = $regla->observacion_documento_id;
            $this->formato_documento_id = $regla->formato_documento_id;
            $this->documento_relacionado_id = $regla->documento_relacionado_id;

            $this->observacionesSeleccionadas = $regla->observacionesDocumento->pluck('id')->map(fn($id) => (string)$id)->toArray();
            if (empty($this->observacionesSeleccionadas) && $regla->observacion_documento_id) { $this->observacionesSeleccionadas = [(string)$regla->observacion_documento_id]; }
            $this->formatosSeleccionados = $regla->formatosDocumento->pluck('id')->map(fn($id) => (string)$id)->toArray();
            if (empty($this->formatosSeleccionados) && $regla->formato_documento_id) { $this->formatosSeleccionados = [(string)$regla->formato_documento_id]; }
            $this->documentosRelacionadosSeleccionados = $regla->documentosRelacionados->pluck('id')->map(fn($id) => (string)$id)->toArray();
            if (empty($this->documentosRelacionadosSeleccionados) && $regla->documento_relacionado_id) { $this->documentosRelacionadosSeleccionados = [(string)$regla->documento_relacionado_id]; }
            $this->tipo_vencimiento_id = $regla->tipo_vencimiento_id;
            $this->dias_validez_documento = $regla->dias_validez_documento;
            $this->dias_gracia_carga = $regla->dias_gracia_carga;
            $this->updatedMandanteId($regla->mandante_id); 
            // Reasignar condiciones DESPUÉS de updatedMandanteId (que carga las listas filtradas pero limpia selecciones)
            $this->condicionesEmpresaSeleccionadas = $regla->condicionesEmpresaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->tiposEmpresaLegalSeleccionados = $regla->tiposEmpresaLegalAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->condicionesPersonaSeleccionadas = $regla->condicionesPersonaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->updatedTipoVencimientoId($regla->tipo_vencimiento_id);
            
            // Asignar Meses ICM DESPUÉS de los métodos updated para evitar que se limpien accidentalmente
            $this->imc_meses_estimados = $regla->imc_meses_estimados;
            
            $this->dias_aviso_vencimiento = $regla->dias_aviso_vencimiento;
            $this->valida_emision = (bool) $regla->valida_emision;
            $this->valida_vencimiento = (bool) $regla->valida_vencimiento;
            $this->requiere_validacion_mandante = (bool) $regla->requiere_validacion_mandante;
            $this->valida_solo_mandante = (bool) $regla->valida_solo_mandante;
            $this->mostrar_historico_documento = (bool) $regla->mostrar_historico_documento;
            $this->is_active = (bool) $regla->is_active;
            
            $this->actualizarNombreEntidadSeleccionada();

            $entidad = $this->getNombreEntidadSeleccionada();
            if ($entidad === 'PERSONA') {
                $this->condicionesPersonaSeleccionadas = $regla->condicionesPersonaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->cargosSeleccionados = $regla->cargosAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->nacionalidadesSeleccionadas = $regla->nacionalidadesAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->tiposPermanenciaSeleccionados = $regla->tiposPermanenciaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->permite_ver_nacionalidad_trabajador = (bool) $regla->permite_ver_nacionalidad_trabajador;
                $this->permite_modificar_nacionalidad_trabajador = (bool) $regla->permite_modificar_nacionalidad_trabajador;
                $this->permite_ver_fecha_nacimiento_trabajador = (bool) $regla->permite_ver_fecha_nacimiento_trabajador;
                $this->permite_modificar_fecha_nacimiento_trabajador = (bool) $regla->permite_modificar_fecha_nacimiento_trabajador;
                $this->condicion_fecha_ingreso_id = $regla->condicion_fecha_ingreso_id;
                $this->fecha_comparacion_ingreso = $regla->fecha_comparacion_ingreso ? $regla->fecha_comparacion_ingreso->format('Y-m-d') : null;
            } elseif ($entidad === 'VEHICULO') {
                $this->tiposVehiculoSeleccionados = $regla->tiposVehiculoAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->subTiposVehiculoSeleccionados = $regla->subTiposVehiculoAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->condicionesVehiculoSeleccionadas = $regla->condicionesVehiculoAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            } elseif ($entidad === 'MAQUINARIA') {
                $this->tiposMaquinariaSeleccionados = $regla->tiposMaquinariaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            } elseif ($entidad === 'EMBARCACION') {
                $this->tiposEmbarcacionSeleccionados = $regla->tiposEmbarcacionAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }
            
            if (in_array($entidad, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'])) {
                 $this->tenenciasSeleccionadas = $regla->tenenciasAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }

            $this->unidadesSeleccionadas = [];
            if ($regla->unidadesOrganizacionales->isNotEmpty()) {
                foreach ($regla->unidadesOrganizacionales as $uoSeleccionada) {
                    $this->unidadesSeleccionadas[] = $this->_getUoHierarchyPath($uoSeleccionada->id);
                }
            } else { $this->agregarUnidadSeleccionada(); }

            $this->criterios = [];
            if ($regla->criteriosAsem->isNotEmpty()) {
                foreach ($regla->criteriosAsem as $criterio) {
                    // Cargar sub-criterios con sus datos condicionales desde la pivot
                    $subCriteriosConfig = $criterio->subCriterios->map(fn($sc) => [
                        'sub_criterio_id'  => (string) $sc->id,
                        'cond_personal_id' => $sc->pivot->tipo_condicion_personal_id ? (string) $sc->pivot->tipo_condicion_personal_id : null,
                        'cond_empresa_id'  => $sc->pivot->tipo_condicion_id ? (string) $sc->pivot->tipo_condicion_id : null,
                    ])->toArray();

                    $this->criterios[] = [
                        'temp_id'               => Str::random(5),
                        'criterio_evaluacion_id' => $criterio->criterio_evaluacion_id, 
                        'sub_criterios_config'  => $subCriteriosConfig,
                        'texto_rechazo_id'      => $criterio->texto_rechazo_id, 
                        'aclaracion_criterio_id' => $criterio->aclaracion_criterio_id,
                    ];
                }
            } else { 
                $this->agregarCriterio(); 
            }
            
            $this->criteriosMandante = [];
            if ($this->requiere_validacion_mandante) {
                if ($regla->criteriosMandante->isNotEmpty()) {
                    foreach ($regla->criteriosMandante as $criterio) {
                        $subCriteriosConfig = $criterio->subCriterios->map(fn($sc) => [
                            'sub_criterio_id'  => (string) $sc->id,
                            'cond_personal_id' => $sc->pivot->tipo_condicion_personal_id ? (string) $sc->pivot->tipo_condicion_personal_id : null,
                            'cond_empresa_id'  => $sc->pivot->tipo_condicion_id ? (string) $sc->pivot->tipo_condicion_id : null,
                        ])->toArray();

                        $this->criteriosMandante[] = [
                            'temp_id'               => Str::random(5),
                            'criterio_evaluacion_id' => $criterio->criterio_evaluacion_id,
                            'sub_criterios_config'  => $subCriteriosConfig,
                            'texto_rechazo_id'      => $criterio->texto_rechazo_id,
                            'aclaracion_criterio_id' => $criterio->aclaracion_criterio_id,
                        ];
                    }
                } else {
                    $this->agregarCriterioMandante();
                }
            }
            
            $this->modoEdicion = true;
            $this->openModal();
        } else {
            session()->flash('error', 'No se encontró la regla documental para editar.');
            Log::warning("Intento de editar regla no existente con ID: {$id}");
        }
    }
    
    private function _getUoHierarchyPath($unidadFinalId) { 
        $path = [ 'uo_nivel1_id' => null, 'uo_nivel2_id' => null, 'uo_nivel3_id' => null, 'uo_nivel4_id' => null, 'final_uo_id' => $unidadFinalId, ];
        $currentUo = UnidadOrganizacionalMandante::with('parent.parent.parent')->find($unidadFinalId);
        if (!$currentUo) { Log::warning("No se encontró la UO con ID: {$unidadFinalId} en _getUoHierarchyPath"); return $path; }
        $hierarchy = []; $tempUo = $currentUo;
        while ($tempUo) { array_unshift($hierarchy, $tempUo->id); $tempUo = $tempUo->parent; }
        if (isset($hierarchy[0])) $path['uo_nivel1_id'] = $hierarchy[0];
        if (isset($hierarchy[1])) $path['uo_nivel2_id'] = $hierarchy[1];
        if (isset($hierarchy[2])) $path['uo_nivel3_id'] = $hierarchy[2];
        if (isset($hierarchy[3])) $path['uo_nivel4_id'] = $hierarchy[3];
        return $path;
    }

    public function store()
    {
        $datosValidados = $this->validate();
        $datosParaGuardar = $this->prepararDatosParaDB($datosValidados);

        try {
            DB::beginTransaction();
            $datosParaGuardar['created_by'] = Auth::id();
            $datosParaGuardar['updated_by'] = Auth::id();

            $regla = ReglaDocumental::create($datosParaGuardar);
            $this->sincronizarRelaciones($regla, $datosValidados); 
            
            DB::commit(); 

            ActualizarEstadoCumplimientoEnMasa::dispatch($regla->mandante_id);

            $this->dispatch('regla-actualizada', mandanteId: $regla->mandante_id);

            session()->flash('success', 'Regla documental creada exitosamente. Se ha iniciado un recálculo de estados en segundo plano.');
            $this->closeModal();
            $this->resetInputFields(); 
        } catch (\Exception $e) {
            DB::rollBack(); 
            Log::error('Error al crear regla: '.$e->getMessage(). ' Trace: ' . $e->getTraceAsString());
            session()->flash('error', 'Ocurrió un error al crear la regla.');
        }
    }

    public function update()
    {
        $datosValidados = $this->validate();
        $datosParaActualizar = $this->prepararDatosParaDB($datosValidados);

        if ($this->reglaDocumentalId) {
            try {
                DB::beginTransaction();
                $regla = ReglaDocumental::with([
                    'cargosAplica', 'nacionalidadesAplica', 'tiposPermanenciaAplica', 'unidadesOrganizacionales',
                    'tiposVehiculoAplica', 'tiposMaquinariaAplica', 'tiposEmbarcacionAplica',
                    'tenenciasAplica', 'criterios'
                ])->findOrFail($this->reglaDocumentalId);
                
                $criteriosOriginales = $regla->criterios->map(function ($criterio) {
                    $item = collect($criterio->toArray())
                        ->only(['criterio_evaluacion_id', 'texto_rechazo_id', 'aclaracion_criterio_id', 'fuente_validacion'])
                        ->all();
                    $item['sub_criterio_ids'] = $criterio->subCriterios->pluck('id')->toArray();
                    return $item;
                })->toArray();
                
                $relacionesOriginales = [
                    'cargos' => $regla->cargosAplica()->pluck('cargos_mandante.id')->toArray(),
                    'nacionalidades' => $regla->nacionalidadesAplica()->pluck('nacionalidades.id')->toArray(),
                    'tiposPermanencia' => $regla->tiposPermanenciaAplica()->pluck('tipos_permanencias.id')->toArray(),
                    'unidades' => $regla->unidadesOrganizacionales()->pluck('unidades_organizacionales_mandante.id')->toArray(),
                    'tiposVehiculo' => $regla->tiposVehiculoAplica()->pluck('tipos_vehiculo.id')->toArray(),
                    'subTiposVehiculo' => $regla->subTiposVehiculoAplica()->pluck('sub_tipos_vehiculo_mandante.id')->toArray(),
                    'tiposMaquinaria' => $regla->tiposMaquinariaAplica()->pluck('tipos_maquinaria.id')->toArray(),
                    'tiposEmbarcacion' => $regla->tiposEmbarcacionAplica()->pluck('tipos_embarcacion.id')->toArray(),
                    'tenencias' => $regla->tenenciasAplica()->pluck('tenencias_vehiculo.id')->toArray(),
                    'condicionesEmpresa' => $regla->condicionesEmpresaAplica()->pluck('tipos_condicion.id')->toArray(),
                    'condicionesPersona' => $regla->condicionesPersonaAplica()->pluck('tipos_condicion_personal.id')->toArray(),
                    'tiposEmpresaLegal' => $regla->tiposEmpresaLegalAplica()->pluck('tipos_empresa_legal.id')->toArray(),
                    'condicionesVehiculo' => $regla->condicionesVehiculoAplica()->pluck('tipo_condicion_vehiculo_id')->toArray(),
                ];

                $datosParaActualizar['updated_by'] = Auth::id();
                $regla->update($datosParaActualizar);
                
                $this->sincronizarRelaciones($regla, $datosValidados); 
                
                DB::commit();

                ActualizarEstadoCumplimientoEnMasa::dispatch($regla->mandante_id);

                $this->dispatch('regla-actualizada', mandanteId: $regla->mandante_id);

                $this->compararYRegistrarCambios($regla, $relacionesOriginales, $criteriosOriginales);

                session()->flash('success', 'Regla documental actualizada exitosamente. Se ha iniciado un recálculo de estados en segundo plano.');
                $this->closeModal();
                $this->resetInputFields(); 

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al actualizar regla: '.$e->getMessage().' Trace: '.$e->getTraceAsString());
                session()->flash('error', 'Ocurrió un error al actualizar la regla. Consulte el log para más detalles.');
            }
        }
    }

    private function sincronizarRelaciones(ReglaDocumental $regla, array $datos)
    {
        $entidad = $this->getNombreEntidadSeleccionada();

        // Sincronizar condiciones empresa y persona (siempre, independiente del tipo de entidad)
        $regla->condicionesEmpresaAplica()->sync($this->condicionesEmpresaSeleccionadas ?? []);
        $regla->tiposEmpresaLegalAplica()->sync($this->tiposEmpresaLegalSeleccionados ?? []);

        // Sincronizar las nuevas referencias múltiples
        $regla->observacionesDocumento()->sync($this->observacionesSeleccionadas ?? []);
        $regla->formatosDocumento()->sync($this->formatosSeleccionados ?? []);
        $regla->documentosRelacionados()->sync($this->documentosRelacionadosSeleccionados ?? []);

        if ($entidad === 'PERSONA') {
            $regla->cargosAplica()->sync($this->cargosSeleccionados ?? []);
            $regla->nacionalidadesAplica()->sync($this->nacionalidadesSeleccionadas ?? []);
            $regla->tiposPermanenciaAplica()->sync($this->tiposPermanenciaSeleccionados ?? []);
            $regla->condicionesPersonaAplica()->sync($this->condicionesPersonaSeleccionadas ?? []);
        } else {
            $regla->cargosAplica()->detach();
            $regla->nacionalidadesAplica()->detach();
            $regla->tiposPermanenciaAplica()->detach();
            $regla->condicionesPersonaAplica()->detach();
        }

        if ($entidad === 'VEHICULO') {
            $regla->tiposVehiculoAplica()->sync($this->tiposVehiculoSeleccionados ?? []);
            $regla->condicionesVehiculoAplica()->sync($this->condicionesVehiculoSeleccionadas ?? []);
        } else {
            $regla->tiposVehiculoAplica()->detach();
            $regla->condicionesVehiculoAplica()->detach();
        }

        if ($entidad === 'MAQUINARIA') {
            $regla->tiposMaquinariaAplica()->sync($this->tiposMaquinariaSeleccionados ?? []);
        } else {
            $regla->tiposMaquinariaAplica()->detach();
        }

        if ($entidad === 'EMBARCACION') {
            $regla->tiposEmbarcacionAplica()->sync($this->tiposEmbarcacionSeleccionados ?? []);
        } else {
            $regla->tiposEmbarcacionAplica()->detach();
        }
        
        if (in_array($entidad, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'])) {
            $regla->tenenciasAplica()->sync($this->tenenciasSeleccionadas ?? []);
        } else {
            $regla->tenenciasAplica()->detach();
        }

        $unidadesParaSincronizar = [];
        if (!empty($datos['unidadesSeleccionadas'])) { 
            foreach ($datos['unidadesSeleccionadas'] as $unidadSet) { 
                if (!empty($unidadSet['final_uo_id'])) { $unidadesParaSincronizar[] = $unidadSet['final_uo_id']; }
            }
        }
        $regla->unidadesOrganizacionales()->sync(array_unique($unidadesParaSincronizar));
        
        $regla->criterios()->delete(); 
        
        if (!empty($datos['criterios'])) {
            foreach ($datos['criterios'] as $criterioData) { 
                $criterioData['fuente_validacion'] = 'asem';
                $subCriteriosConfig = $criterioData['sub_criterios_config'] ?? [];
                $criterioData = $this->limpiarCriterioData($criterioData);
                $nuevoCriterio = $regla->criterios()->create($criterioData); 
                // Sincronizar sub-criterios con datos condicionales en la pivot
                $this->_sincronizarSubCriteriosCondicionales($nuevoCriterio, $subCriteriosConfig);
            }
        }

        if ($this->requiere_validacion_mandante && !empty($datos['criteriosMandante'])) {
            foreach ($datos['criteriosMandante'] as $criterioData) {
                $criterioData['fuente_validacion'] = 'mandante';
                $subCriteriosConfig = $criterioData['sub_criterios_config'] ?? [];
                $criterioData = $this->limpiarCriterioData($criterioData);
                $nuevoCriterio = $regla->criterios()->create($criterioData);
                $this->_sincronizarSubCriteriosCondicionales($nuevoCriterio, $subCriteriosConfig);
            }
        }
    }
    
    /**
     * Sincroniza los sub-criterios de un criterio con sus datos condicionales en la pivot.
     * Cada entrada de $config tiene: sub_criterio_id, cond_personal_id, cond_empresa_id.
     */
    private function _sincronizarSubCriteriosCondicionales(
        \App\Models\ReglaDocumentalCriterio $criterio,
        array $config
    ): void {
        // Eliminar todas las filas pivot anteriores de este criterio
        DB::table('regla_criterio_sub_criterio')
            ->where('regla_documental_criterio_id', $criterio->id)
            ->delete();

        foreach ($config as $item) {
            $subCriterioId = (int) ($item['sub_criterio_id'] ?? 0);
            if (!$subCriterioId) continue; // Omitir filas vacías

            DB::table('regla_criterio_sub_criterio')->insert([
                'regla_documental_criterio_id'  => $criterio->id,
                'sub_criterio_id'               => $subCriterioId,
                'tipo_condicion_personal_id'    => $item['cond_personal_id'] ?: null,
                'tipo_condicion_id'             => $item['cond_empresa_id']  ?: null,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);
        }
    }

    private function limpiarCriterioData(array $criterioData): array
    {
        unset($criterioData['temp_id']);
        unset($criterioData['sub_criterios_config']); // Manejado por _sincronizarSubCriteriosCondicionales
        $criterioData['texto_rechazo_id'] = $criterioData['texto_rechazo_id'] ?: null;
        $criterioData['aclaracion_criterio_id'] = $criterioData['aclaracion_criterio_id'] ?: null;
        return $criterioData;
    }

    private function compararYRegistrarCambios(ReglaDocumental $regla, array $relacionesOriginales, array $criteriosOriginales): void
    {
        $cambios = [];
        $entidad = $this->getNombreEntidadSeleccionada();

        // ====================================================================
        // COMPARACION DE RELACIONES (CARGOS, UNIDADES, ETC.)
        // Todos los labels en MAYUSCULAS sin acentos para evitar mojibake
        // ====================================================================
        $mapaRelaciones = [
            'PERSONA' => [
                'CARGOS'        => ['ids_originales' => $relacionesOriginales['cargos'],        'ids_nuevos' => $this->cargosSeleccionados],
                'NACIONALIDADES' => ['ids_originales' => $relacionesOriginales['nacionalidades'], 'ids_nuevos' => $this->nacionalidadesSeleccionadas],
                'TIPOS DE PERMANENCIA' => ['ids_originales' => $relacionesOriginales['tiposPermanencia'], 'ids_nuevos' => $this->tiposPermanenciaSeleccionados],
            ],
            'EMPRESA' => [
                'TIPOS DE EMPRESA LEGAL' => ['ids_originales' => $relacionesOriginales['tiposEmpresaLegal'] ?? [], 'ids_nuevos' => $this->tiposEmpresaLegalSeleccionados],
            ],
            'VEHICULO'    => [
                'TIPOS DE VEHICULO'    => ['ids_originales' => $relacionesOriginales['tiposVehiculo'],    'ids_nuevos' => $this->tiposVehiculoSeleccionados],
                'SUB-TIPOS DE VEHICULO' => ['ids_originales' => $relacionesOriginales['subTiposVehiculo'] ?? [], 'ids_nuevos' => $this->subTiposVehiculoSeleccionados],
                'CONDICIONES DE VEHICULO' => ['ids_originales' => $relacionesOriginales['condicionesVehiculo'] ?? [], 'ids_nuevos' => $this->condicionesVehiculoSeleccionadas],
            ],
            'MAQUINARIA'  => ['TIPOS DE MAQUINARIA'  => ['ids_originales' => $relacionesOriginales['tiposMaquinaria'],  'ids_nuevos' => $this->tiposMaquinariaSeleccionados]],
            'EMBARCACION' => ['TIPOS DE EMBARCACION' => ['ids_originales' => $relacionesOriginales['tiposEmbarcacion'], 'ids_nuevos' => $this->tiposEmbarcacionSeleccionados]],
        ];
        $unidadesNuevas = collect($this->unidadesSeleccionadas)->pluck('final_uo_id')->filter()->unique()->all();
        $mapaRelaciones['comun'] = ['UNIDADES ORGANIZACIONALES' => ['ids_originales' => $relacionesOriginales['unidades'], 'ids_nuevos' => $unidadesNuevas]];
        if (in_array($entidad, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'])) {
            $mapaRelaciones['comun']['TENENCIAS'] = ['ids_originales' => $relacionesOriginales['tenencias'], 'ids_nuevos' => $this->tenenciasSeleccionadas];
        }
        $relacionesAComparar = array_merge($mapaRelaciones['comun'], $mapaRelaciones[$entidad] ?? []);

        foreach ($relacionesAComparar as $nombreRelacion => $datos) {
            $originales = $datos['ids_originales'];
            $nuevos     = $datos['ids_nuevos'] ?? [];
            sort($originales);
            sort($nuevos);

            if ($originales != $nuevos) {
                $nombresOld = [];
                $nombresNew = [];
                $modelo = match($nombreRelacion) {
                    'CARGOS'                   => CargoMandante::class,
                    'NACIONALIDADES'            => Nacionalidad::class,
                    'TIPOS DE PERMANENCIA'      => TipoPermanencia::class,
                    'TIPOS DE EMPRESA LEGAL'    => \App\Models\TipoEmpresaLegal::class,
                    'TIPOS DE VEHICULO'         => TipoVehiculo::class,
                    'TIPOS DE MAQUINARIA'       => TipoMaquinaria::class,
                    'TIPOS DE EMBARCACION'      => TipoEmbarcacion::class,
                    'TENENCIAS'                 => TenenciaVehiculo::class,
                    'UNIDADES ORGANIZACIONALES' => UnidadOrganizacionalMandante::class,
                    default                     => null,
                };
                $campo = ($nombreRelacion === 'CARGOS') ? 'nombre_cargo'
                       : (($nombreRelacion === 'UNIDADES ORGANIZACIONALES') ? 'nombre_unidad' : 'nombre');
                if ($modelo) {
                    $nombresOld = $modelo::whereIn('id', $originales)->pluck($campo)->toArray();
                    $nombresNew = $modelo::whereIn('id', $nuevos)->pluck($campo)->toArray();
                }
                $cambios[$nombreRelacion] = [
                    'old' => implode(', ', $nombresOld) ?: 'NINGUNA',
                    'new' => implode(', ', $nombresNew) ?: 'NINGUNA',
                ];
            }
        }

        if (!empty($cambios)) {
            activity('Regla Documental')
                ->performedOn($regla)
                ->causedBy(Auth::user())
                ->withProperties(['relations' => $cambios])
                ->log('RELACIONES MODIFICADAS');
        }

        // ====================================================================
        // COMPARACION DE CRITERIOS (ASEM Y MANDANTE)
        // Esta logica estaba declarada pero NUNCA implementada — BUG CRITICO
        // ====================================================================
        $criteriosNuevos = [];
        foreach ($this->criterios as $c) {
            $subIds = [];
            if (!empty($c['sub_criterios_config'])) {
                foreach($c['sub_criterios_config'] as $cfg) {
                    if (!empty($cfg['sub_criterio_id'])) {
                        $subIds[] = (int)$cfg['sub_criterio_id'];
                    }
                }
            }
            $criteriosNuevos[] = [
                'criterio_evaluacion_id' => $c['criterio_evaluacion_id'] ?: null,
                'sub_criterio_ids'       => $subIds,
                'texto_rechazo_id'       => $c['texto_rechazo_id']       ?: null,
                'aclaracion_criterio_id' => $c['aclaracion_criterio_id'] ?: null,
                'fuente_validacion'      => 'asem',
            ];
        }
        if ($this->requiere_validacion_mandante && !empty($this->criteriosMandante)) {
            foreach ($this->criteriosMandante as $c) {
                $subIds = [];
                if (!empty($c['sub_criterios_config'])) {
                    foreach($c['sub_criterios_config'] as $cfg) {
                        if (!empty($cfg['sub_criterio_id'])) {
                            $subIds[] = (int)$cfg['sub_criterio_id'];
                        }
                    }
                }
                $criteriosNuevos[] = [
                    'criterio_evaluacion_id' => $c['criterio_evaluacion_id'] ?: null,
                    'sub_criterio_ids'       => $subIds,
                    'texto_rechazo_id'       => $c['texto_rechazo_id']       ?: null,
                    'aclaracion_criterio_id' => $c['aclaracion_criterio_id'] ?: null,
                    'fuente_validacion'      => 'mandante',
                ];
            }
        }

        // Normalizar para comparacion (convertir todo a string para evitar diferencias int/string)
        $normalizarCriterio = fn($c) => [
            (string)($c['criterio_evaluacion_id'] ?? ''),
            implode(',', array_map('strval', $c['sub_criterio_ids'] ?? [])),
            (string)($c['texto_rechazo_id']        ?? ''),
            (string)($c['aclaracion_criterio_id']  ?? ''),
            (string)($c['fuente_validacion']        ?? ''),
        ];

        $originalesNorm = array_map($normalizarCriterio, $criteriosOriginales);
        $nuevosNorm     = array_map($normalizarCriterio, $criteriosNuevos);
        sort($originalesNorm);
        sort($nuevosNorm);

        if ($originalesNorm !== $nuevosNorm) {
            
            $formatearCriterioDict = function (array $c): array {
                $subIds = $c['sub_criterio_ids'] ?? [];
                $subNombres = SubCriterio::whereIn('id', $subIds)->pluck('nombre')->toArray();
                
                return [
                    'Fuente' => strtoupper($c['fuente_validacion'] ?? 'ASEM'),
                    'Criterio Evaluación' => CriterioEvaluacion::find($c['criterio_evaluacion_id'])?->nombre_criterio ?? ('ID:' . ($c['criterio_evaluacion_id'] ?? '-')),
                    'Sub-criterios' => !empty($subNombres) ? implode(', ', $subNombres) : 'Ninguno',
                    'Texto de Rechazo' => $c['texto_rechazo_id'] ? (TextoRechazo::find($c['texto_rechazo_id'])?->titulo ?? 'ID:' . $c['texto_rechazo_id']) : 'Ninguno',
                    'Aclaración Criterio' => $c['aclaracion_criterio_id'] ? (AclaracionCriterio::find($c['aclaracion_criterio_id'])?->titulo ?? 'ID:' . $c['aclaracion_criterio_id']) : 'Ninguna',
                ];
            };

            $criteriosOrigFull = array_map($formatearCriterioDict, $criteriosOriginales);
            $criteriosNewFull  = array_map($formatearCriterioDict, $criteriosNuevos);

            // Formatea lista de criterios como texto legible (Legacy support)
            $formatearCriterios = function (array $lista): string {
                if (empty($lista)) {
                    return '(NINGUNO)';
                }
                $lineas = [];
                foreach ($lista as $c) {
                    $fuente   = strtoupper($c['fuente_validacion'] ?? 'ASEM');
                    $criterio = CriterioEvaluacion::find($c['criterio_evaluacion_id'])?->nombre_criterio
                                ?? ('ID:' . ($c['criterio_evaluacion_id'] ?? '-'));
                    
                    $subIds = $c['sub_criterio_ids'] ?? [];
                    $subNombres = SubCriterio::whereIn('id', $subIds)->pluck('nombre')->toArray();
                    $sub = !empty($subNombres) ? implode(', ', $subNombres) : '(SIN SUB-CRITERIO)';

                    $rechazo  = $c['texto_rechazo_id']
                                ? (TextoRechazo::find($c['texto_rechazo_id'])?->titulo ?? 'ID:' . $c['texto_rechazo_id'])
                                : '(SIN TEXTO RECHAZO)';
                    $aclar    = $c['aclaracion_criterio_id']
                                ? (AclaracionCriterio::find($c['aclaracion_criterio_id'])?->titulo ?? 'ID:' . $c['aclaracion_criterio_id'])
                                : '(SIN ACLARACION)';
                    $lineas[] = "[{$fuente}] {$criterio} | SUBS: {$sub} | RECHAZO: {$rechazo} | ACLAR: {$aclar}";
                }
                return implode(' || ', $lineas);
            };

            activity('Regla Documental')
                ->performedOn($regla)
                ->causedBy(Auth::user())
                ->withProperties([
                    'criterios_originales' => $criteriosOrigFull,
                    'criterios_nuevos'     => $criteriosNewFull,
                    'criterios' => [
                        'old' => $formatearCriterios($criteriosOriginales),
                        'new' => $formatearCriterios($criteriosNuevos),
                    ],
                ])
                ->log('CRITERIOS MODIFICADOS');
        }
    }

    
    public function toggleActivo($id) { 
        $regla = ReglaDocumental::find($id);
        if ($regla) {
            try { 
                $regla->is_active = !$regla->is_active; 
                $regla->updated_by = Auth::id(); 
                $regla->save(); 
                
                ActualizarEstadoCumplimientoEnMasa::dispatch($regla->mandante_id);

                $this->dispatch('regla-actualizada', mandanteId: $regla->mandante_id);

                $accion = $regla->is_active ? 'activada' : 'desactivada'; 
                session()->flash('success', "Regla documental {$accion} exitosamente. Se ha iniciado un recálculo de estados en segundo plano.");
            } catch (\Exception $e) { 
                Log::error("Error al cambiar estado de regla ID {$id}: " . $e->getMessage()); 
                session()->flash('error', 'Ocurrió un error al cambiar el estado de la regla.');
            }
        } else { 
            session()->flash('error', 'Regla documental no encontrada.');
        }
    }

    public function confirmarEliminacion($id) { 
        $regla = ReglaDocumental::with('nombreDocumento')->find($id);
        if ($regla) { $this->reglaIdParaEliminar = $regla->id; $this->nombreReglaParaEliminar = "Regla para el documento '" . ($regla->nombreDocumento->nombre ?? 'Desconocido') . "' (ID: {$regla->id})"; $this->showConfirmDeleteModal = true;
        } else { session()->flash('error', 'Regla documental no encontrada para eliminar.'); Log::warning("Intento de confirmar eliminación para regla no existente ID: {$id}");}
    }

    public function deleteRegla()
    {
        $regla = ReglaDocumental::find($this->reglaIdParaEliminar);
        if ($regla) {
            try {
                DB::beginTransaction();
                $mandanteIdAfectado = $regla->mandante_id;
                $regla->criterios()->delete(); 
                $regla->unidadesOrganizacionales()->detach();
                $regla->cargosAplica()->detach(); 
                $regla->nacionalidadesAplica()->detach(); 
                $regla->tiposVehiculoAplica()->detach(); 
                $regla->tiposMaquinariaAplica()->detach();
                $regla->tiposEmbarcacionAplica()->detach(); 
                $regla->tenenciasAplica()->detach();
                $regla->delete(); 
                DB::commit();

                ActualizarEstadoCumplimientoEnMasa::dispatch($mandanteIdAfectado);

                $this->dispatch('regla-actualizada', mandanteId: $mandanteIdAfectado);

                session()->flash('success', 'Regla documental eliminada exitosamente. Se ha iniciado un recálculo de estados en segundo plano.');
            } catch (\Exception $e) {
                DB::rollBack(); Log::error("Error al eliminar regla ID {$this->reglaIdParaEliminar}: " . $e->getMessage());
                session()->flash('error', 'Ocurrió un error al eliminar la regla documental.');
            }
        } else {
            session()->flash('error', 'Regla documental no encontrada para eliminar.');
            Log::warning("Intento de eliminar regla no existente con ID desde modal: {$this->reglaIdParaEliminar}");
        }
        $this->showConfirmDeleteModal = false; 
        $this->reglaIdParaEliminar = null; 
        $this->nombreReglaParaEliminar = null;
    }
    
    public function render()
    {
        $query = ReglaDocumental::query()
            ->select('reglas_documentales.*') 
            ->with([
                'mandante:id,razon_social', 
                'tipoEntidadControlada:id,nombre_entidad', 
                'nombreDocumento:id,nombre',
                'unidadesOrganizacionales:id,nombre_unidad', 
                'condicionesEmpresaAplica:id,nombre',
                'condicionesPersonaAplica:id,nombre',
                'cargosAplica:id,nombre_cargo', 
                'nacionalidadesAplica:id,nombre',
                'tiposVehiculoAplica:id,nombre', 
                'tiposMaquinariaAplica:id,nombre',
                'tiposEmbarcacionAplica:id,nombre', 
                'tenenciasAplica:id,nombre',
                'createdByUser:id,name',
                'updatedByUser:id,name'
            ]);

        if (!empty($this->filtroMandanteId)) { $query->where('reglas_documentales.mandante_id', $this->filtroMandanteId); }
        if (!empty($this->filtroTipoEntidadId)) { $query->where('reglas_documentales.tipo_entidad_controlada_id', $this->filtroTipoEntidadId); }
        if (!empty($this->filtroNombreDocumento)) { $query->join('nombre_documentos', 'reglas_documentales.nombre_documento_id', '=', 'nombre_documentos.id')->where('nombre_documentos.nombre', 'like', '%' . $this->filtroNombreDocumento . '%');}

        if ($this->sortBy === 'mandantes.razon_social') { $query->join('mandantes', 'reglas_documentales.mandante_id', '=', 'mandantes.id')->orderBy('mandantes.razon_social', $this->sortDirection);
        } elseif ($this->sortBy === 'tipos_entidad_controlable.nombre_entidad') { $query->join('tipos_entidad_controlable', 'reglas_documentales.tipo_entidad_controlada_id', '=', 'tipos_entidad_controlable.id')->orderBy('tipos_entidad_controlable.nombre_entidad', $this->sortDirection);
        } elseif ($this->sortBy === 'nombre_documentos.nombre') { if (empty($this->filtroNombreDocumento)) { $query->join('nombre_documentos', 'reglas_documentales.nombre_documento_id', '=', 'nombre_documentos.id'); } $query->orderBy('nombre_documentos.nombre', $this->sortDirection);
        } else { $query->orderBy($this->sortBy, $this->sortDirection); }
        
        if ($this->sortBy !== 'reglas_documentales.id') { $query->orderBy('reglas_documentales.id', 'desc'); }

        $reglas = $query->paginate(10);

        $this->imcTotalPrincipal = 0;
        $imcDesglose = [
            'EMBARCACION' => 0, 'EMPRESA' => 0, 'MAQUINARIA' => 0, 'PERSONA' => 0, 'VEHICULO' => 0,
        ];
        $totalTrabajadoresPersona = 0;
        $totalDocumentosEsperadosGlobal = 0;
        $totalEntidadesControladas = 0;
        
        if (!empty($this->filtroMandanteId)) {
            $reglasActivas = ReglaDocumental::with([
                    'tipoEntidadControlada', 
                    'unidadesOrganizacionales', 
                    'condicionesPersonaAplica', 
                    'cargosAplica', 
                    'nacionalidadesAplica', 
                    'tiposPermanenciaAplica', 
                    'condicionesEmpresaAplica',
                    'tiposVehiculoAplica',
                    'tenenciasAplica',
                    'tiposMaquinariaAplica',
                    'tiposEmbarcacionAplica'
                ])
                ->where('mandante_id', $this->filtroMandanteId)
                ->where('is_active', true)
                ->get();
                
            $this->imcTotalPrincipal = $reglasActivas->sum('imc');
            
            $docsEsperadosDesglose = [
                'EMBARCACION' => 0, 'EMPRESA' => 0, 'MAQUINARIA' => 0, 'PERSONA' => 0, 'VEHICULO' => 0,
            ];

            foreach ($reglasActivas as $r) {
                $entidad = strtoupper($r->tipoEntidadControlada->nombre_entidad ?? 'OTRO');
                if (isset($imcDesglose[$entidad]) && $r->imc) {
                    $imcDesglose[$entidad] += $r->imc;
                }
                
                // Sumatoria global de documentos esperados
                $afectados = $r->contarAfectados();
                if ($r->imc) {
                    $monto = ($afectados * $r->imc);
                    $totalDocumentosEsperadosGlobal += $monto;
                    if (isset($docsEsperadosDesglose[$entidad])) {
                        $docsEsperadosDesglose[$entidad] += $monto;
                    }
                }
            }
            
            // 1. Trabajadores únicos activos para este mandante
            $totalTrabajadoresPersona = \App\Models\TrabajadorVinculacion::where('is_active', true)
                ->whereHas('unidadOrganizacionalMandante', function($q) {
                    $q->where('mandante_id', $this->filtroMandanteId);
                })
                ->whereHas('trabajador', function($q) {
                    $q->whereNull('deleted_at');
                })
                ->distinct('trabajador_id')
                ->count('trabajador_id');

            // 2. Vehículos únicos activos
            $totalVehiculos = \App\Models\VehiculoAsignacion::where('is_active', true)
                ->whereHas('unidadOrganizacionalMandante', function($q) {
                    $q->where('mandante_id', $this->filtroMandanteId);
                })
                ->distinct('vehiculo_id')
                ->count('vehiculo_id');

            // 3. Maquinarias únicas activas
            $totalMaquinarias = \App\Models\MaquinariaAsignacion::where('is_active', true)
                ->whereHas('unidadOrganizacionalMandante', function($q) {
                    $q->where('mandante_id', $this->filtroMandanteId);
                })
                ->distinct('maquinaria_id')
                ->count('maquinaria_id');

            // 4. Embarcaciones únicas activas
            $totalEmbarcaciones = \App\Models\EmbarcacionAsignacion::where('is_active', true)
                ->whereHas('unidadOrganizacionalMandante', function($q) {
                    $q->where('mandante_id', $this->filtroMandanteId);
                })
                ->distinct('embarcacion_id')
                ->count('embarcacion_id');

            // 5. Entidad Empresa (Contratistas y Subs)
            $contratistasActivos = \App\Models\Contratista::where('is_active', true)
                ->whereHas('solicitudesVinculacion', function($q) {
                    $q->where('mandante_id', $this->filtroMandanteId)
                      ->where('estado', 'APROBADA');
                })
                ->get();

            $totalEmpresas = $contratistasActivos->filter(function($c) {
                $currentId = $c->id;
                
                while ($currentId) {
                    $padreRelation = \App\Models\SolicitudVinculacion::where('contratista_id', $currentId)
                        ->where('tipo_solicitud', 'SUBCONTRATISTA')
                        ->where('estado', 'APROBADA')
                        ->first();

                    if (!$padreRelation || !$padreRelation->contratista_padre_id) {
                        break; // No tiene padre, su linaje está limpio (es principal y él es activo)
                    }

                    $padre = \App\Models\Contratista::find($padreRelation->contratista_padre_id);
                    if (!$padre || !$padre->is_active) {
                        return false; // El padre está inactivo, descarta al hijo
                    }

                    $currentId = $padre->id; // Sube al siguiente nivel
                }
                
                return true;
            })->count();

            // Total de Entidades Controladas (Suma global)
            $totalEntidadesControladas = $totalTrabajadoresPersona + $totalVehiculos + $totalMaquinarias + $totalEmbarcaciones + $totalEmpresas;

            ksort($imcDesglose);
            ksort($docsEsperadosDesglose);
        }

        return view('livewire.gestion-reglas-documentales', [
            'reglas' => $reglas,
            'listaMandantes' => $this->listaMandantes,
            'listaTiposEntidad' => $this->listaTiposEntidadControlable,
            'imcDesglose' => $imcDesglose,
            'totalTrabajadoresPersona' => $totalTrabajadoresPersona,
            'totalVehiculos' => $totalVehiculos ?? 0,
            'totalMaquinarias' => $totalMaquinarias ?? 0,
            'totalEmbarcaciones' => $totalEmbarcaciones ?? 0,
            'totalEmpresas' => $totalEmpresas ?? 0,
            'totalDocumentosEsperadosGlobal' => $totalDocumentosEsperadosGlobal,
            'docsEsperadosDesglose' => $docsEsperadosDesglose ?? [],
            'totalEntidadesControladas' => $totalEntidadesControladas
        ])->layout('layouts.app');
    }

    public function incrementarMesesImc($id)
    {
        $regla = ReglaDocumental::find($id);
        if ($regla) {
            $oldVal = $regla->imc_meses_estimados;
            
            if ($oldVal === null) {
                // Partir desde el valor auto-calculado actual, no desde 1
                if ($regla->dias_validez_documento > 0) {
                    $base = (int) round($regla->dias_validez_documento / 30.44);
                    $newVal = max(1, $base + 1);
                } else {
                    $newVal = 1;
                }
            } else {
                $newVal = $oldVal + 1;
            }
            
            $regla->update(['imc_meses_estimados' => $newVal]);
            $this->registrarCambioRapidoImc($regla, $oldVal, $newVal);
        }
    }

    public function decrementarMesesImc($id)
    {
        $regla = ReglaDocumental::find($id);
        if ($regla) {
            $oldVal = $regla->imc_meses_estimados;
            
            if ($oldVal === null) {
                // Decrementar desde el valor auto-calculado actual
                if ($regla->dias_validez_documento > 0) {
                    $base = (int) round($regla->dias_validez_documento / 30.44);
                    $newVal = max(1, $base - 1);
                    $regla->update(['imc_meses_estimados' => $newVal]);
                    $this->registrarCambioRapidoImc($regla, $oldVal, $newVal);
                }
                // Si no tiene días, no hay base para decrementar
            } elseif ($oldVal > 1) {
                $newVal = $oldVal - 1;
                $regla->update(['imc_meses_estimados' => $newVal]);
                $this->registrarCambioRapidoImc($regla, $oldVal, $newVal);
            } elseif ($oldVal == 1) {
                // Restablecer a auto
                $regla->update(['imc_meses_estimados' => null]);
                $this->registrarCambioRapidoImc($regla, $oldVal, null);
            }
        }
    }

    private function registrarCambioRapidoImc($regla, $oldVal, $newVal)
    {
        $oldDisplay = $oldVal === null ? 'Vacío (Auto)' : $oldVal;
        $newDisplay = $newVal === null ? 'Vacío (Auto)' : $newVal;
        
        activity()
            ->performedOn($regla)
            ->causedBy(Auth::user())
            ->withProperties([
                'attributes' => [
                    'imc_meses_estimados' => [
                        'old' => $oldDisplay,
                        'new' => $newDisplay
                    ]
                ]
            ])
            ->log('actualizado');
    }

    public function verHistorial($reglaId)
    {
        $regla = ReglaDocumental::with(['mandante', 'tipoEntidadControlada', 'nombreDocumento'])->find($reglaId);
        if (!$regla) {
            session()->flash('error', 'No se pudo encontrar la regla para ver el historial.');
            return;
        }

        $this->reglaParaHistorial = $regla;
        $this->nombreReglaHistorial = $regla->nombreDocumento->nombre ?? "Regla ID: {$reglaId}";

        $actividades = Activity::where('subject_type', ReglaDocumental::class)
            ->where('subject_id', $reglaId)
            ->with('causer:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // Traducir IDs en atributos automÍƒÆ’Í†â€™Íƒâ€ Í¢â‚¬â„¢ÍƒÆ’Í¢â‚¬Å¡Íƒâ€šáticos para transparencia total
        $this->historialActividad = $actividades->map(function($actividad) {
            if ($actividad->properties && $actividad->properties->has('attributes')) {
                $properties = $actividad->properties->toArray();
                $mappedAttributes = [];
                $mappedOld = [];

                foreach ($properties['attributes'] as $key => $value) {
                    $traducido = $this->traducirAtributoIdATexto($key, $value);
                    $mappedAttributes[$traducido['label']] = $traducido['value'];
                    
                    if (isset($properties['old'][$key])) {
                        $oldTraducido = $this->traducirAtributoIdATexto($key, $properties['old'][$key]);
                        $mappedOld[$traducido['label']] = $oldTraducido['value'];
                    }
                }
                
                $actividad->properties = collect([
                    'attributes' => $mappedAttributes,
                    'old' => $mappedOld,
                    'relations' => $properties['relations'] ?? null
                ]);
            }
            return $actividad;
        });
        
        $this->showHistoryModal = true;
    }

    private function traducirAtributoIdATexto(string $key, $value): array
    {
        $label = $this->validationAttributes[$key] ?? $key;
        $textValue = $value;

        // Lista de campos booleanos
        $booleanFields = [
            'is_active', 'valida_emision', 'valida_vencimiento', 'mostrar_historico_documento',
            'permite_ver_nacionalidad_trabajador', 'permite_modificar_nacionalidad_trabajador',
            'permite_ver_fecha_nacimiento_trabajador', 'permite_modificar_fecha_nacimiento_trabajador',
            'requiere_validacion_mandante'
        ];

        if (in_array($key, $booleanFields)) {
            $textValue = $value ? 'Sí' : 'No';
        } elseif ($value !== null) {
            try {
                $textValue = match ($key) {
                    'mandante_id' => Mandante::find($value)?->razon_social ?? "ID:{$value}",
                    'tipo_entidad_controlada_id' => TipoEntidadControlable::find($value)?->nombre_entidad ?? "ID:{$value}",
                    'nombre_documento_id', 'documento_relacionado_id' => NombreDocumento::find($value)?->nombre ?? "ID:{$value}",
                    'aplica_empresa_condicion_id' => TipoCondicion::find($value)?->nombre ?? "ID:{$value}",
                    'aplica_persona_condicion_id' => TipoCondicionPersonal::find($value)?->nombre ?? "ID:{$value}",
                    'condicionesVehiculoSeleccionadas' => \App\Models\TipoCondicionVehiculo::find($value)?->nombre ?? "ID:{$value}",
                    'condicion_fecha_ingreso_id' => CondicionFechaIngreso::find($value)?->nombre ?? "ID:{$value}",
                    'observacion_documento_id' => ObservacionDocumento::find($value)?->titulo ?? "ID:{$value}",
                    'formato_documento_id' => FormatoDocumentoMuestra::find($value)?->nombre ?? "ID:{$value}",
                    'tipo_vencimiento_id' => TipoVencimiento::find($value)?->nombre ?? "ID:{$value}",
                    default => $value,
                };
            } catch (\Exception $e) {
                $textValue = "ID:{$value} (Error al traducir)";
            }
        }

        return ['label' => $label, 'value' => $textValue];
    }

    public function exportarExcel()
    {
        $filtros = [
            'mandante_id' => $this->filtroMandanteId,
            'tipo_entidad_id' => $this->filtroTipoEntidadId,
            'nombre_documento' => $this->filtroNombreDocumento,
        ];

        $ids = $this->exportSelectedOnly ? array_filter($this->reglasSeleccionadas) : [];

        $this->showExportOptionsModal = false;

        \App\Services\AuditService::log('reglas-documentales', "Exportó Reglas Documentales a EXCEL");

        return Excel::download(
            new ReglasDocumentalesExport($filtros, $ids, $this->exportIncludeHistory), 
            'reglas_documentales_' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function exportarPDF()
    {
        $query = ReglaDocumental::query()
            ->with(['mandante', 'tipoEntidadControlada', 'nombreDocumento', 'tipoVencimiento']);

        if ($this->exportSelectedOnly && !empty($this->reglasSeleccionadas)) {
            $query->whereIn('id', array_filter($this->reglasSeleccionadas));
        } else {
            if (!empty($this->filtroMandanteId)) { $query->where('mandante_id', $this->filtroMandanteId); }
            if (!empty($this->filtroTipoEntidadId)) { $query->where('tipo_entidad_controlada_id', $this->filtroTipoEntidadId); }
            if (!empty($this->filtroNombreDocumento)) { 
                $query->whereHas('nombreDocumento', function($q) { $q->where('nombre', 'like', '%' . $this->filtroNombreDocumento . '%'); });
            }
        }

        $reglas = $query->get();
        $historial = collect();

        if ($this->exportIncludeHistory) {
            $historial = Activity::where('subject_type', ReglaDocumental::class)
                ->whereIn('subject_id', $reglas->pluck('id'))
                ->with('causer')
                ->latest()
                ->get();
        }

        $this->showExportOptionsModal = false;

        \App\Services\AuditService::log('reglas-documentales', "Exportó Reglas Documentales a PDF");

        $pdf = Pdf::loadView('reports.reglas-documentales-pdf', [
            'reglas' => $reglas,
            'historial' => $historial,
            'incluirHistorial' => $this->exportIncludeHistory,
            'fecha' => now()->format('d/m/Y H:i'),
            'usuario' => Auth::user()->name
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'reglas_documentales_' . now()->format('Y-m-d_H-i') . '.pdf');
    }

    public function updatedValidaSoloMandante($value)
    {
        if ($value) {
            $this->requiere_validacion_mandante = true;
            if (empty($this->criteriosMandante)) {
                $this->agregarCriterioMandante();
            }
        }
    }

    public function updatedRequiereValidacionMandante($value)
    {
        if ($value) {
            if (empty($this->criteriosMandante)) {
                $this->agregarCriterioMandante();
            }
        } else {
            $this->valida_solo_mandante = false;
        }
    }

    public function updatedSeleccionarTodas($value)
    {
        if ($value) {
            $query = ReglaDocumental::query();
            
            if (!empty($this->filtroMandanteId)) { $query->where('mandante_id', $this->filtroMandanteId); }
            if (!empty($this->filtroTipoEntidadId)) { $query->where('tipo_entidad_controlada_id', $this->filtroTipoEntidadId); }
            if (!empty($this->filtroNombreDocumento)) { 
                $query->whereHas('nombreDocumento', function($q) { 
                    $q->where('nombre', 'like', '%' . $this->filtroNombreDocumento . '%'); 
                }); 
            }

            $this->reglasSeleccionadas = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->reglasSeleccionadas = [];
        }
    }
    public function exportarReglaIndividual($formato)
    {
        if (!$this->reglaParaHistorial) return;

        $originalExportSelectedOnly = $this->exportSelectedOnly;
        $originalReglasSeleccionadas = $this->reglasSeleccionadas;
        $originalExportIncludeHistory = $this->exportIncludeHistory;

        $this->exportSelectedOnly = true;
        $this->reglasSeleccionadas = [$this->reglaParaHistorial->id];
        $this->exportIncludeHistory = true;

        $response = ($formato === 'xlsx') ? $this->exportarExcel() : $this->exportarPDF();

        $this->exportSelectedOnly = $originalExportSelectedOnly;
        $this->reglasSeleccionadas = $originalReglasSeleccionadas;
        $this->exportIncludeHistory = $originalExportIncludeHistory;

        return $response;
    }

    public function descargarPlantilla()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReglasDocumentalesExport([], [999999]), "plantilla_reglas_documentales.xlsx");
    }

    public function importarExcel()
    {
        try {
            $this->validate([
                "archivoImport" => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->archivoImport, 'excel_import', 'GESTION_REGLAS_IMPORT');
            throw $e;
        }

        try {
            $import = new \App\Imports\ReglasDocumentalesImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $this->archivoImport->getRealPath());

            $this->importResults = [
                "creados" => $import->successes,
                "actualizados" => $import->updates,
                "errores" => $import->failures
            ];

            $this->dispatch("regla-actualizada");
            \Illuminate\Support\Facades\Log::info("Importación masiva completada por el usuario " . auth()->id());
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en importación masiva: " . $e->getMessage());
            $this->addError("archivoImport", "Error al procesar el archivo: " . $e->getMessage());
        }
    }


    public function generarReporteImc()
    {
        $this->validate([
            'mandantesSeleccionadosParaImc' => 'required|array|min:1',
        ], [
            'mandantesSeleccionadosParaImc.required' => 'Debe seleccionar al menos una Principal para generar el reporte.',
        ]);

        $this->showReporteImcModal = false;

        \App\Services\AuditService::log('reglas-documentales', "Generó Reporte Ejecutivo IMC (Excel)");

        return (new \App\Exports\ReporteImcExport($this->mandantesSeleccionadosParaImc, $this->imcSoloActivas))
            ->download('reporte_ejecutivo_imc_' . now()->format('Y-m-d_H-i') . '.xlsx');
    }

    public function generarReporteImcPDF()
    {
        $this->validate([
            'mandantesSeleccionadosParaImc' => 'required|array|min:1',
        ], [
            'mandantesSeleccionadosParaImc.required' => 'Debe seleccionar al menos una Principal para generar el reporte.',
        ]);

        $this->showReporteImcModal = false;

        \App\Services\AuditService::log('reglas-documentales', "Generó Reporte Ejecutivo IMC (PDF)");

        $mandantes = Mandante::whereIn('id', $this->mandantesSeleccionadosParaImc)->orderBy('razon_social')->get();
        
        $query = ReglaDocumental::with(['mandante', 'tipoEntidadControlada', 'nombreDocumento', 'tipoVencimiento'])
            ->whereIn('mandante_id', $this->mandantesSeleccionadosParaImc);

        if ($this->imcSoloActivas) {
            $query->where('is_active', true);
        }

        $reglas = $query->get();

        $totalReglas = $reglas->count();
        $reglasActivas = $reglas->where('is_active', true)->count();
        $imcTotal = $reglas->sum('imc');
        $cargasEstimadasAnio = $imcTotal * 12;

        $resumenPorMandante = [];
        foreach ($mandantes as $mandante) {
            $reglasMandante = $reglas->where('mandante_id', $mandante->id);
            if ($reglasMandante->isEmpty()) continue;

            $resumenEntidades = [];
            foreach ($reglasMandante->groupBy('tipo_entidad_controlada_id') as $entidadId => $reglasEntidad) {
                $entidadNombre = $reglasEntidad->first()->tipoEntidadControlada->nombre_entidad ?? 'Otra';
                $imcEntidad = $reglasEntidad->sum('imc');
                $resumenEntidades[] = [
                    'nombre' => $entidadNombre,
                    'total' => $reglasEntidad->count(),
                    'activas' => $reglasEntidad->where('is_active', true)->count(),
                    'imc' => $imcEntidad,
                    'cargas_anio' => $imcEntidad * 12,
                    'reglas_detalle' => $reglasEntidad->sortByDesc('imc')->values()
                ];
            }

            usort($resumenEntidades, function($a, $b) {
                return $b['imc'] <=> $a['imc'];
            });

            $resumenPorMandante[] = [
                'mandante' => $mandante,
                'total_reglas' => $reglasMandante->count(),
                'imc_total' => $reglasMandante->sum('imc'),
                'entidades' => $resumenEntidades
            ];
        }

        $topReglas = $reglas->sortByDesc('imc')->take(10)->values();

        $pdf = Pdf::loadView('reports.reporte-imc-pdf', [
            'totalReglas' => $totalReglas,
            'reglasActivas' => $reglasActivas,
            'imcTotal' => $imcTotal,
            'cargasEstimadasAnio' => $cargasEstimadasAnio,
            'resumenPorMandante' => $resumenPorMandante,
            'topReglas' => $topReglas,
            'fecha' => now()->format('d/m/Y H:i'),
            'usuario' => Auth::user()->name
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'reporte_ejecutivo_imc_' . now()->format('Y-m-d_H-i') . '.pdf');
    }
}
