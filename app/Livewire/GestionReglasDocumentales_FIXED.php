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
    use WithPagination, WithFileUploads;

    public $isOpen = false;
    public $reglaDocumentalId;
    public $modoEdicion = false;

    // --- Propiedades del formulario ---
    public $mandante_id;
    public $tipo_entidad_controlada_id;
    public $nombre_documento_id;
    public $valor_nominal_documento = 1;
    public $aplica_empresa_condicion_id;
    public $aplica_persona_condicion_id;
    public $rut_especificos;
    public $rut_excluidos;
    public $condicion_fecha_ingreso_id;
    public $fecha_comparacion_ingreso;
    public $observacion_documento_id;
    public $formato_documento_id;
    public $documento_relacionado_id;
    public $tipo_vencimiento_id;
    public $dias_validez_documento;
    public $dias_gracia_carga;
    public $imc_meses_estimados;
    public $dias_aviso_vencimiento = 30;
    public $valida_emision = false;
    public $valida_vencimiento = false;
    public $requiere_validacion_mandante = false;
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
    public $tiposVehiculoSeleccionados = [];
    public $tiposMaquinariaSeleccionados = [];
    public $tiposEmbarcacionSeleccionados = [];
    public $tenenciasSeleccionadas = [];
    public $listaMandantes;
    public Collection $listaTiposEntidadControlable;
    public $listaNombresDocumento;
    public $listaTiposCondicionEmpresa;
    public $listaTiposCondicionPersonal;
    public $listaCargosMandante = [];
    public $listaNacionalidades;
    public $listaTiposVehiculo;
    public $listaTiposMaquinaria;
    public $listaTiposEmbarcacion;
    public $listaTenenciasVehiculo;
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

    // Propiedades de Filtrado y Ordenamiento
    public $filtroMandanteId = '';
    public $filtroTipoEntidadId = '';
    public $filtroNombreDocumento = '';
    public $sortBy = 'reglas_documentales.id';
    public $sortDirection = 'desc';

    // Propiedades para ExportaciÃƒÆ’Ã‚Â³n Avanzada
    public $showExportOptionsModal = false;
    public $exportSelectedOnly = false;
    public $exportIncludeHistory = false;
    public $reglasSeleccionadas = [];
    public $seleccionarTodas = false;

    // Propiedades para ImportaciÃƒÆ’Ã‚Â³n Masiva
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
            'criterios' => 'array',
            'criterios.*.criterio_evaluacion_id' => 'required|exists:criterios_evaluacion,id',
            'criterios.*.sub_criterio_id' => 'nullable|exists:sub_criterios,id',
            'criterios.*.texto_rechazo_id' => 'nullable|exists:textos_rechazo,id',
            'criterios.*.aclaracion_criterio_id' => 'nullable|exists:aclaraciones_criterio,id',
            'criteriosMandante' => 'required_if:requiere_validacion_mandante,true|array',
            'criteriosMandante.*.criterio_evaluacion_id' => 'required_if:requiere_validacion_mandante,true|exists:criterios_evaluacion,id',
            'criteriosMandante.*.sub_criterio_id' => 'nullable|exists:sub_criterios,id',
            'criteriosMandante.*.texto_rechazo_id' => 'nullable|exists:textos_rechazo,id',
            'criteriosMandante.*.aclaracion_criterio_id' => 'nullable|exists:aclaraciones_criterio,id',
            'unidadesSeleccionadas' => 'array|min:1', 
            'unidadesSeleccionadas.*.final_uo_id' => 'required|exists:unidades_organizacionales_mandante,id',
        ];

        $entidad = $this->getNombreEntidadSeleccionada();

        if ($entidad === 'PERSONA') {
            $rules['aplica_persona_condicion_id'] = 'nullable|exists:tipos_condicion_personal,id';
            $rules['cargosSeleccionados'] = 'nullable|array'; 
            $rules['cargosSeleccionados.*'] = 'integer|exists:cargos_mandante,id'; 
            $rules['nacionalidadesSeleccionadas'] = 'nullable|array'; 
            $rules['nacionalidadesSeleccionadas.*'] = 'integer|exists:nacionalidades,id';
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

        return $rules;
    }

    protected $validationAttributes = [
        'mandante_id' => 'Mandante',
        'tipo_entidad_controlada_id' => 'Entidad Controlada',
        'nombre_documento_id' => 'Documento',
        'aplica_empresa_condicion_id' => 'CondiciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Empresa',
        'aplica_persona_condicion_id' => 'CondiciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Persona',
        'cargosSeleccionados' => 'Cargos Aplicables',
        'cargosSeleccionados.*' => 'Cargo Aplicable',
        'nacionalidadesSeleccionadas' => 'Nacionalidades Aplicables',
        'nacionalidadesSeleccionadas.*' => 'Nacionalidad Aplicable',
        'tiposVehiculoSeleccionados' => 'Tipos de VehÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culo Aplicables',
        'tiposVehiculoSeleccionados.*' => 'Tipo de VehÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culo Aplicable',
        'tiposMaquinariaSeleccionados' => 'Tipos de Maquinaria Aplicables',
        'tiposMaquinariaSeleccionados.*' => 'Tipo de Maquinaria Aplicable',
        'tiposEmbarcacionSeleccionados' => 'Tipos de EmbarcaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Aplicables',
        'tiposEmbarcacionSeleccionados.*' => 'Tipo de EmbarcaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Aplicable',
        'tenenciasSeleccionadas' => 'Tenencias de Activo Aplicables',
        'tenenciasSeleccionadas.*' => 'Tenencia de Activo Aplicable',
        'rut_especificos' => 'Identificadores EspecÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ficos',
        'rut_excluidos' => 'Identificadores Excluidos',
        'condicion_fecha_ingreso_id' => 'OpciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Fechas Ingreso',
        'fecha_comparacion_ingreso' => 'Fecha de ComparaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n',
        'observacion_documento_id' => 'ObservaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n Documento',
        'formato_documento_id' => 'Formato Documento',
        'documento_relacionado_id' => 'Documento Relacionado',
        'tipo_vencimiento_id' => 'Tipo de Vencimiento',
        'dias_validez_documento' => 'DÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as Validez Documento',
        'dias_gracia_carga' => 'DÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­as de Gracia para Carga',
        'requiere_validacion_mandante' => 'Requiere ValidaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n de Mandante',
        'criterios.*.criterio_evaluacion_id' => 'Criterio de EvaluaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n (ASEM - fila :index)',
        'criteriosMandante' => 'Criterios de EvaluaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n del Mandante',
        'criteriosMandante.*.criterio_evaluacion_id' => 'Criterio de EvaluaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n (Mandante - fila :index)',
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
        $this->listaNombresDocumento = NombreDocumento::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposCondicionEmpresa = TipoCondicion::where('is_active', true)->orderBy('nombre')->get();
        $this->listaTiposCondicionPersonal = TipoCondicionPersonal::where('is_active', true)->orderBy('nombre')->get();
        $this->listaNacionalidades = Nacionalidad::where('is_active', true)->orderBy('nombre')->get();
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
        $this->aplica_persona_condicion_id = null;
        $this->cargosSeleccionados = [];
        $this->nacionalidadesSeleccionadas = [];
        $this->tiposVehiculoSeleccionados = [];
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
        $this->listaCargosMandante = $value ? CargoMandante::where('mandante_id', $value)->where('is_active', true)->orderBy('nombre_cargo')->get() : [];
        $this->cargosSeleccionados = [];
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
            'temp_id' => Str::random(5), 
            'criterio_evaluacion_id' => '', 
            'sub_criterio_id' => '', 
            'texto_rechazo_id' => '', 
            'aclaracion_criterio_id' => ''
        ]; 
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
    public function quitarSeleccionDeTiposVehiculo() { $this->tiposVehiculoSeleccionados = []; }
    public function seleccionarTodosLosTiposVehiculo() { if (isset($this->listaTiposVehiculo) && $this->listaTiposVehiculo->isNotEmpty()) { $this->tiposVehiculoSeleccionados = $this->listaTiposVehiculo->pluck('id')->toArray(); } else { $this->tiposVehiculoSeleccionados = []; } }
    public function quitarSeleccionDeTiposMaquinaria() { $this->tiposMaquinariaSeleccionados = []; }
    public function seleccionarTodosLosTiposMaquinaria() { if (isset($this->listaTiposMaquinaria) && $this->listaTiposMaquinaria->isNotEmpty()) { $this->tiposMaquinariaSeleccionados = $this->listaTiposMaquinaria->pluck('id')->toArray(); } else { $this->tiposMaquinariaSeleccionados = []; } }
    public function quitarSeleccionDeTiposEmbarcacion() { $this->tiposEmbarcacionSeleccionados = []; }
    public function seleccionarTodosLosTiposEmbarcacion() { if (isset($this->listaTiposEmbarcacion) && $this->listaTiposEmbarcacion->isNotEmpty()) { $this->tiposEmbarcacionSeleccionados = $this->listaTiposEmbarcacion->pluck('id')->toArray(); } else { $this->tiposEmbarcacionSeleccionados = []; } }
    public function quitarSeleccionDeTenencias() { $this->tenenciasSeleccionadas = []; }
    public function seleccionarTodasLasTenencias() { if (isset($this->listaTenenciasVehiculo) && $this->listaTenenciasVehiculo->isNotEmpty()) { $this->tenenciasSeleccionadas = $this->listaTenenciasVehiculo->pluck('id')->toArray(); } else { $this->tenenciasSeleccionadas = []; } }

    public function agregarCriterioMandante()
    {
        $this->criteriosMandante[] = [
            'temp_id' => Str::random(5),
            'criterio_evaluacion_id' => '',
            'sub_criterio_id' => '',
            'texto_rechazo_id' => '',
            'aclaracion_criterio_id' => ''
        ];
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
        $this->valor_nominal_documento = 1; $this->aplica_empresa_condicion_id = null; $this->aplica_persona_condicion_id = null;
        $this->cargosSeleccionados = []; $this->nacionalidadesSeleccionadas = []; $this->tiposVehiculoSeleccionados = [];
        $this->tiposMaquinariaSeleccionados = []; $this->tiposEmbarcacionSeleccionados = []; $this->tenenciasSeleccionadas = [];
        $this->rut_especificos = null; $this->rut_excluidos = null; $this->condicion_fecha_ingreso_id = null;
        $this->fecha_comparacion_ingreso = null; $this->observacion_documento_id = null; $this->formato_documento_id = null;
        $this->documento_relacionado_id = null; $this->tipo_vencimiento_id = null; $this->dias_validez_documento = null;
        $this->dias_gracia_carga = null;
        $this->imc_meses_estimados = null;
        $this->dias_aviso_vencimiento = 30; $this->valida_emision = false; $this->valida_vencimiento = false;
        $this->requiere_validacion_mandante = false;
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
            'aplica_empresa_condicion_id', 'aplica_persona_condicion_id',
            'condicion_fecha_ingreso_id', 'observacion_documento_id',
            'formato_documento_id', 'documento_relacionado_id', 'tipo_vencimiento_id',
            'dias_validez_documento', 'dias_gracia_carga'
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
            $data['aplica_persona_condicion_id'] = null;
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
            'unidadesOrganizacionales', 'criteriosAsem', 'criteriosMandante', 'cargosAplica', 'nacionalidadesAplica',
            'tiposVehiculoAplica', 'tiposMaquinariaAplica', 'tiposEmbarcacionAplica', 'tenenciasAplica',
        ])->find($id); 

        if ($regla) {
            $this->reglaDocumentalId = $regla->id;
            $this->mandante_id = $regla->mandante_id;
            $this->tipo_entidad_controlada_id = $regla->tipo_entidad_controlada_id;
            $this->nombre_documento_id = $regla->nombre_documento_id;
            $this->valor_nominal_documento = $regla->valor_nominal_documento;
            $this->aplica_empresa_condicion_id = $regla->aplica_empresa_condicion_id;
            $this->rut_especificos = $regla->rut_especificos;
            $this->rut_excluidos = $regla->rut_excluidos;
            $this->observacion_documento_id = $regla->observacion_documento_id;
            $this->formato_documento_id = $regla->formato_documento_id;
            $this->documento_relacionado_id = $regla->documento_relacionado_id;
            $this->tipo_vencimiento_id = $regla->tipo_vencimiento_id;
            $this->dias_validez_documento = $regla->dias_validez_documento;
            $this->dias_gracia_carga = $regla->dias_gracia_carga;
            $this->dias_aviso_vencimiento = $regla->dias_aviso_vencimiento;
            $this->valida_emision = (bool) $regla->valida_emision;
            $this->valida_vencimiento = (bool) $regla->valida_vencimiento;
            $this->requiere_validacion_mandante = (bool) $regla->requiere_validacion_mandante;
            $this->mostrar_historico_documento = (bool) $regla->mostrar_historico_documento;
            $this->is_active = (bool) $regla->is_active;

            $this->actualizarNombreEntidadSeleccionada();
            $this->updatedMandanteId($regla->mandante_id); 
            $this->updatedTipoVencimientoId($regla->tipo_vencimiento_id);

            $entidad = $this->getNombreEntidadSeleccionada();
            if ($entidad === 'PERSONA') {
                $this->aplica_persona_condicion_id = $regla->aplica_persona_condicion_id;
                $this->cargosSeleccionados = $regla->cargosAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->nacionalidadesSeleccionadas = $regla->nacionalidadesAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->permite_ver_nacionalidad_trabajador = (bool) $regla->permite_ver_nacionalidad_trabajador;
                $this->permite_modificar_nacionalidad_trabajador = (bool) $regla->permite_modificar_nacionalidad_trabajador;
                $this->permite_ver_fecha_nacimiento_trabajador = (bool) $regla->permite_ver_fecha_nacimiento_trabajador;
                $this->permite_modificar_fecha_nacimiento_trabajador = (bool) $regla->permite_modificar_fecha_nacimiento_trabajador;
                $this->condicion_fecha_ingreso_id = $regla->condicion_fecha_ingreso_id;
                $this->fecha_comparacion_ingreso = $regla->fecha_comparacion_ingreso ? $regla->fecha_comparacion_ingreso->format('Y-m-d') : null;
            } elseif ($entidad === 'VEHICULO') {
                $this->tiposVehiculoSeleccionados = $regla->tiposVehiculoAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
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
                    $this->criterios[] = [
                        'temp_id' => Str::random(5),
                        'criterio_evaluacion_id' => $criterio->criterio_evaluacion_id, 
                        'sub_criterio_id' => $criterio->sub_criterio_id,
                        'texto_rechazo_id' => $criterio->texto_rechazo_id, 
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
                        $this->criteriosMandante[] = [
                            'temp_id' => Str::random(5),
                            'criterio_evaluacion_id' => $criterio->criterio_evaluacion_id,
                            'sub_criterio_id' => $criterio->sub_criterio_id,
                            'texto_rechazo_id' => $criterio->texto_rechazo_id,
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
            session()->flash('error', 'No se encontrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ la regla documental para editar.');
            Log::warning("Intento de editar regla no existente con ID: {$id}");
        }
    }
    
    private function _getUoHierarchyPath($unidadFinalId) { 
        $path = [ 'uo_nivel1_id' => null, 'uo_nivel2_id' => null, 'uo_nivel3_id' => null, 'uo_nivel4_id' => null, 'final_uo_id' => $unidadFinalId, ];
        $currentUo = UnidadOrganizacionalMandante::with('parent.parent.parent')->find($unidadFinalId);
        if (!$currentUo) { Log::warning("No se encontrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ la UO con ID: {$unidadFinalId} en _getUoHierarchyPath"); return $path; }
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

            session()->flash('success', 'Regla documental creada exitosamente. Se ha iniciado un recÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lculo de estados en segundo plano.'); 
            $this->closeModal();
            $this->resetInputFields(); 
        } catch (\Exception $e) {
            DB::rollBack(); 
            Log::error('Error al crear regla: '.$e->getMessage(). ' Trace: ' . $e->getTraceAsString());
            session()->flash('error', 'OcurriÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ un error al crear la regla.');
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
                    'cargosAplica', 'nacionalidadesAplica', 'unidadesOrganizacionales',
                    'tiposVehiculoAplica', 'tiposMaquinariaAplica', 'tiposEmbarcacionAplica',
                    'tenenciasAplica', 'criterios'
                ])->findOrFail($this->reglaDocumentalId);
                
                $criteriosOriginales = $regla->criterios->map(function ($criterio) {
                    return collect($criterio->toArray())
                        ->only(['criterio_evaluacion_id', 'sub_criterio_id', 'texto_rechazo_id', 'aclaracion_criterio_id', 'fuente_validacion'])
                        ->all();
                })->toArray();
                
                $relacionesOriginales = [
                    'cargos' => $regla->cargosAplica()->pluck('cargos_mandante.id')->toArray(),
                    'nacionalidades' => $regla->nacionalidadesAplica()->pluck('nacionalidades.id')->toArray(),
                    'unidades' => $regla->unidadesOrganizacionales()->pluck('unidades_organizacionales_mandante.id')->toArray(),
                    'tiposVehiculo' => $regla->tiposVehiculoAplica()->pluck('tipos_vehiculo.id')->toArray(),
                    'tiposMaquinaria' => $regla->tiposMaquinariaAplica()->pluck('tipos_maquinaria.id')->toArray(),
                    'tiposEmbarcacion' => $regla->tiposEmbarcacionAplica()->pluck('tipos_embarcacion.id')->toArray(),
                    'tenencias' => $regla->tenenciasAplica()->pluck('tenencias_vehiculo.id')->toArray(),
                ];

                $datosParaActualizar['updated_by'] = Auth::id();
                $regla->update($datosParaActualizar);
                
                $this->sincronizarRelaciones($regla, $datosValidados); 
                
                DB::commit();

                ActualizarEstadoCumplimientoEnMasa::dispatch($regla->mandante_id);

                $this->dispatch('regla-actualizada', mandanteId: $regla->mandante_id);

                $this->compararYRegistrarCambios($regla, $relacionesOriginales, $criteriosOriginales);

                session()->flash('success', 'Regla documental actualizada exitosamente. Se ha iniciado un recÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lculo de estados en segundo plano.');
                $this->closeModal();
                $this->resetInputFields(); 

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al actualizar regla: '.$e->getMessage().' Trace: '.$e->getTraceAsString());
                session()->flash('error', 'OcurriÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ un error al actualizar la regla. Consulte el log para mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡s detalles.');
            }
        }
    }

    private function sincronizarRelaciones(ReglaDocumental $regla, array $datos)
    {
        $entidad = $this->getNombreEntidadSeleccionada();

        if ($entidad === 'PERSONA') {
            $regla->cargosAplica()->sync($this->cargosSeleccionados ?? []);
            $regla->nacionalidadesAplica()->sync($this->nacionalidadesSeleccionadas ?? []);
        } else {
            $regla->cargosAplica()->detach();
            $regla->nacionalidadesAplica()->detach();
        }

        if ($entidad === 'VEHICULO') {
            $regla->tiposVehiculoAplica()->sync($this->tiposVehiculoSeleccionados ?? []);
        } else {
            $regla->tiposVehiculoAplica()->detach();
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
                $criterioData = $this->limpiarCriterioData($criterioData);
                $regla->criterios()->create($criterioData); 
            }
        }

        if ($this->requiere_validacion_mandante && !empty($datos['criteriosMandante'])) {
            foreach ($datos['criteriosMandante'] as $criterioData) {
                $criterioData['fuente_validacion'] = 'mandante';
                $criterioData = $this->limpiarCriterioData($criterioData);
                $regla->criterios()->create($criterioData);
            }
        }
    }
    
    private function limpiarCriterioData(array $criterioData): array
    {
        unset($criterioData['temp_id']);
        $criterioData['sub_criterio_id'] = $criterioData['sub_criterio_id'] ?: null;
        $criterioData['texto_rechazo_id'] = $criterioData['texto_rechazo_id'] ?: null;
        $criterioData['aclaracion_criterio_id'] = $criterioData['aclaracion_criterio_id'] ?: null;
        return $criterioData;
    }

    private function compararYRegistrarCambios(ReglaDocumental $regla, array $relacionesOriginales, array $criteriosOriginales): void
    {
        $cambios = [];
        $entidad = $this->getNombreEntidadSeleccionada();

        $mapaRelaciones = [
            'PERSONA' => [
                'Cargos' => ['ids_originales' => $relacionesOriginales['cargos'], 'ids_nuevos' => $this->cargosSeleccionados],
                'Nacionalidades' => ['ids_originales' => $relacionesOriginales['nacionalidades'], 'ids_nuevos' => $this->nacionalidadesSeleccionadas],
            ],
            'VEHICULO' => ['Tipos de VehÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culo' => ['ids_originales' => $relacionesOriginales['tiposVehiculo'], 'ids_nuevos' => $this->tiposVehiculoSeleccionados]],
            'MAQUINARIA' => ['Tipos de Maquinaria' => ['ids_originales' => $relacionesOriginales['tiposMaquinaria'], 'ids_nuevos' => $this->tiposMaquinariaSeleccionados]],
            'EMBARCACION' => ['Tipos de EmbarcaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n' => ['ids_originales' => $relacionesOriginales['tiposEmbarcacion'], 'ids_nuevos' => $this->tiposEmbarcacionSeleccionados]],
        ];
        $unidadesNuevas = collect($this->unidadesSeleccionadas)->pluck('final_uo_id')->filter()->unique()->all();
        $mapaRelaciones['comun'] = ['Unidades Organizacionales' => ['ids_originales' => $relacionesOriginales['unidades'], 'ids_nuevos' => $unidadesNuevas]];
        if(in_array($entidad, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'])) { $mapaRelaciones['comun']['Tenencias'] = ['ids_originales' => $relacionesOriginales['tenencias'], 'ids_nuevos' => $this->tenenciasSeleccionadas]; }
        $relacionesAComparar = array_merge($mapaRelaciones['comun'], $mapaRelaciones[$entidad] ?? []);

        foreach ($relacionesAComparar as $nombreRelacion => $datos) {
            $originales = $datos['ids_originales'];
            $nuevos = $datos['ids_nuevos'] ?? [];
            sort($originales);
            sort($nuevos);

            if ($originales != $nuevos) {
                // Resolviendo nombres reales de las relaciones para el historial
                $nombresOld = []; $nombresNew = [];
                $modelo = match($nombreRelacion) {
                    'Cargos' => CargoMandante::class,
                    'Nacionalidades' => Nacionalidad::class,
                    'Tipos de VehÃƒÆ’Ã‚Â­culo' => TipoVehiculo::class,
                    'Tipos de Maquinaria' => TipoMaquinaria::class,
                    'Tipos de EmbarcaciÃƒÆ’Ã‚Â³n' => TipoEmbarcacion::class,
                    'Tenencias' => TenenciaVehiculo::class,
                    'Unidades Organizacionales' => UnidadOrganizacionalMandante::class,
                    default => null
                };
                $campo = ($nombreRelacion === 'Cargos') ? 'nombre_cargo' : (($nombreRelacion === 'Unidades Organizacionales') ? 'nombre_unidad' : 'nombre');
                if ($modelo) {
                    $nombresOld = $modelo::whereIn('id', $originales)->pluck($campo)->toArray();
                    $nombresNew = $modelo::whereIn('id', $nuevos)->pluck($campo)->toArray();
                }
                $cambios[$nombreRelacion] = [
                    'old' => implode(', ', $nombresOld) ?: 'ninguna',
                    'new' => implode(', ', $nombresNew) ?: 'ninguna'
                ];
            }
        }
        if (!empty($cambios)) {
            activity('Regla Documental')
               ->performedOn($regla)
               ->causedBy(Auth::user())
               ->withProperties(['relations' => $cambios])
               ->log('Relaciones modificadas');
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
                session()->flash('success', "Regla documental {$accion} exitosamente. Se ha iniciado un recÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lculo de estados en segundo plano.");
            } catch (\Exception $e) { 
                Log::error("Error al cambiar estado de regla ID {$id}: " . $e->getMessage()); 
                session()->flash('error', 'OcurriÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ un error al cambiar el estado de la regla.');
            }
        } else { 
            session()->flash('error', 'Regla documental no encontrada.');
        }
    }

    public function confirmarEliminacion($id) { 
        $regla = ReglaDocumental::with('nombreDocumento')->find($id);
        if ($regla) { $this->reglaIdParaEliminar = $regla->id; $this->nombreReglaParaEliminar = "Regla para el documento '" . ($regla->nombreDocumento->nombre ?? 'Desconocido') . "' (ID: {$regla->id})"; $this->showConfirmDeleteModal = true;
        } else { session()->flash('error', 'Regla documental no encontrada para eliminar.'); Log::warning("Intento de confirmar eliminaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n para regla no existente ID: {$id}");}
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

                session()->flash('success', 'Regla documental eliminada exitosamente. Se ha iniciado un recÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡lculo de estados en segundo plano.');
            } catch (\Exception $e) {
                DB::rollBack(); Log::error("Error al eliminar regla ID {$this->reglaIdParaEliminar}: " . $e->getMessage());
                session()->flash('error', 'OcurriÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³ un error al eliminar la regla documental.');
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

        return view('livewire.gestion-reglas-documentales', [
            'reglas' => $reglas,
            'listaMandantes' => $this->listaMandantes,
            'listaTiposEntidad' => $this->listaTiposEntidadControlable, 
        ])->layout('layouts.app');
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

        // Traducir IDs en atributos automÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ticos para transparencia total
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
            $textValue = $value ? 'SÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­' : 'No';
        } elseif ($value !== null) {
            try {
                $textValue = match ($key) {
                    'mandante_id' => Mandante::find($value)?->razon_social ?? "ID:{$value}",
                    'tipo_entidad_controlada_id' => TipoEntidadControlable::find($value)?->nombre_entidad ?? "ID:{$value}",
                    'nombre_documento_id', 'documento_relacionado_id' => NombreDocumento::find($value)?->nombre ?? "ID:{$value}",
                    'aplica_empresa_condicion_id' => TipoCondicion::find($value)?->nombre ?? "ID:{$value}",
                    'aplica_persona_condicion_id' => TipoCondicionPersonal::find($value)?->nombre ?? "ID:{$value}",
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
        $this->validate([
            "archivoImport" => "required|mimes:xlsx,xls,csv|max:10240",
        ]);

        try {
            $import = new \App\Imports\ReglasDocumentalesImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $this->archivoImport->getRealPath());

            $this->importResults = [
                "creados" => $import->successes,
                "actualizados" => $import->updates,
                "errores" => $import->failures
            ];

            $this->dispatch("regla-actualizada");
            \Illuminate\Support\Facades\Log::info("ImportaciÃƒÆ’Ã‚Â³n masiva completada por el usuario " . auth()->id());
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error en importaciÃƒÆ’Ã‚Â³n masiva: " . $e->getMessage());
            $this->addError("archivoImport", "Error al procesar el archivo: " . $e->getMessage());
        }
    }

}
// TEST PERSISTENCE AT 2026-03-05
