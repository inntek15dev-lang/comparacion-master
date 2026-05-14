<?php

namespace App\Livewire\Contratista;

use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CarpetaVerificacion;
use App\Models\CalendarioVerificacion;
use App\Models\DocumentoVerificacion;
use App\Models\RequisitoVerificacion;
use App\Models\ExclusionVerificacionPeriodo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

use App\Traits\ValidatesFileUpload;

class Verificacion extends Component
{
    use WithFileUploads;
    use ValidatesFileUpload;

    public $vinculaciones = [];
    public $vinculacion_seleccionada_id;
    public $periodos = [];
    public $filtro_id_registro = '';
    public $filtro_ia = ''; // IA_OK, IA_PENDIENTE
    public $estado_plazo = ''; // DENTRO_PLAZO, FUERA_PLAZO
    public $modal_confirmacion_visible = false;
    public $declaracion_aceptada = false;
    
    // Navegación desde Legacy
    public $is_from_legacy = false;
    
    // Estado de Carga (Nueva Lógica)
    public $anio_seleccionado = '2026';
    public $mes_seleccionado;
    public $carpeta_actual;
    public $archivos = []; // Array temporal para archivos [requisito_id => [file1, file2]]
    public $inicio_global = null;
    
    public function mount()
    {
        $this->cargarVinculaciones();

        // ── Lógica de Deep-Linking desde Legacy o Externo ──
        $v = request()->query('v');
        $a = request()->query('a');
        $m = request()->query('m');
        $from = request()->query('from');

        if ($from === 'legacy') {
            $this->is_from_legacy = true;
        }

        if ($v && $a && $m) {
            $this->detallarPeriodos($v);
            $this->seleccionarPeriodo($a, $m);
        }
    }

    public function verificarBloqueoSecuencial()
    {
        if (!$this->vinculacion_seleccionada_id || !$this->mes_seleccionado || !$this->anio_seleccionado) {
            return null;
        }

        $vinculacion = ContratistaUnidadOrganizacional::find($this->vinculacion_seleccionada_id);
        if (!$vinculacion) return null;

        $fechaInicioContrato = Carbon::parse($vinculacion->fecha_inicio_verifica)->startOfMonth();
        $cursorMes = Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1)->startOfMonth()->subMonth();
        $ultimoPeriodoExigible = null;

        while ($cursorMes >= $fechaInicioContrato) {
            $excluidoCheckStr = $cursorMes->copy()->addMonth()->startOfMonth()->toDateString();
            $fueExcluidoCheck = ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
                ->where('periodo', $excluidoCheckStr)
                ->exists();
            
            if (!$fueExcluidoCheck) {
                $ultimoPeriodoExigible = $cursorMes->copy();
                break;
            }
            $cursorMes->subMonth();
        }

        if ($ultimoPeriodoExigible) {
            $carpetaPrevia = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
                ->where('anio', $ultimoPeriodoExigible->year)
                ->where('mes', $ultimoPeriodoExigible->month)
                ->first();

            $nombreMesExigible = mb_strtoupper($this->getNombreMes($ultimoPeriodoExigible->month)) . ' ' . $ultimoPeriodoExigible->year;

            if (!$carpetaPrevia) {
                return "❌ No puede enviar este periodo. Primero debe enviar el periodo anterior obligatorio: $nombreMesExigible.";
            }

            if ($carpetaPrevia->estado_revision !== 'EMITIDO') {
                return "❌ No puede enviar este periodo. El certificado del periodo anterior ($nombreMesExigible) aún no está EMITIDO.";
            }
        }

        return null;
    }

    public function cargarVinculaciones()
    {
        $user = Auth::user();
        $contratista = $user->contratista;

        if (!$contratista) {
            $this->vinculaciones = [];
            return;
        }

        // Obtener vinculaciones directas de la tabla pivote que tengan verificacion activa
        $query = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
            ->where('verifica', true)
            ->with(['unidadOrganizacionalMandante', 'dependencia', 'unidadOrganizacionalMandante.mandante', 'tipoContrato']);

        if ($this->filtro_id_registro) {
            $query->where('id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }

        $this->vinculaciones = $query->get();
        
        // Al recargar vinculaciones (por cambio de año o filtro), limpiamos el detalle actual
        $this->periodos = [];
        $this->vinculacion_seleccionada_id = null;
        $this->mes_seleccionado = null;
        $this->carpeta_actual = null;
    }

    public function detallarPeriodos($vinculacionId, $limpiarSeleccion = true)
    {
        $this->vinculacion_seleccionada_id = $vinculacionId;
        
        // Ya no calculamos el año aquí, confiamos en lo que tenga el filtro anio_seleccionado
        
        $vinculacion = ContratistaUnidadOrganizacional::with('unidadOrganizacionalMandante.mandante')->find($vinculacionId);
        
        if (!$vinculacion) return;

        $mandanteId = $vinculacion->unidadOrganizacionalMandante->mandante_id;
        $this->periodos = [];
        $anioActual = date('Y');
        
        // Buscar inicio global del mandante para establecer la "valla" mínima
        $regInicioGlobal = CalendarioVerificacion::where('mandante_id', $mandanteId)
            ->where('is_inicio', true)
            ->first();
        
        $fechaInicioGlobal = $regInicioGlobal 
            ? Carbon::create($regInicioGlobal->anio, $regInicioGlobal->mes, 1)->startOfMonth()
            : null;

        $this->inicio_global = $regInicioGlobal ? [
            'mes' => $regInicioGlobal->nombre_mes,
            'anio' => $regInicioGlobal->anio,
            'periodo' => $regInicioGlobal->nombre_periodo
        ] : null;

        // Vamos a mostrar los 12 meses de NÓMINA del año seleccionado
        for ($m = 1; $m <= 12; $m++) {
            // REGLA DE DESFASE: El registro de configuración (Calendario/Carpeta) 
            // está siempre en el mes siguiente (m + 1)
            // BUSQUEDA DE CALENDARIO (Usa el mes configurado para verificar esta nómina: m+1)
            $dbMesCal = $m + 1;
            $dbAnioCal = $this->anio_seleccionado;
            if ($dbMesCal > 12) {
                $dbMesCal = 1;
                $dbAnioCal = (int)$this->anio_seleccionado + 1;
            }

            $cal = CalendarioVerificacion::where('mandante_id', $mandanteId)
                ->where('anio', $dbAnioCal)
                ->where('mes', $dbMesCal)
                ->first();
            
            $inicioMesNomina = Carbon::create($this->anio_seleccionado, $m, 1)->startOfMonth();
            $finMesNomina = $inicioMesNomina->copy()->endOfMonth();

            // 1. Vigencia Técnica
            $fueraVigencia = false;
            if ($finMesNomina->lt($vinculacion->fecha_inicio_verifica)) $fueraVigencia = true;
            if ($vinculacion->fecha_fin_verifica && $inicioMesNomina->gt($vinculacion->fecha_fin_verifica)) $fueraVigencia = true;

            // 2. Inicio Global (Hito de partida)
            $antesDeInicio = false;
            if ($fechaInicioGlobal && $inicioMesNomina < $fechaInicioGlobal->copy()->subMonth()) {
                $antesDeInicio = true;
            }

            // 3. Verificar Exclusión Manual (No Informado por Mandante)
            $fechaCalendarioStr = $inicioMesNomina->copy()->addMonth()->startOfMonth()->toDateString();
            $excluido = \App\Models\ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $vinculacionId)
                ->where('periodo', $fechaCalendarioStr)
                ->exists();

            // BUSQUEDA DE CARPETA (Honesta: Mes de Nómina = Mes de BD)
            $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $vinculacionId)
                ->where('anio', $this->anio_seleccionado)
                ->where('mes', $m)
                ->first();

            $hoy = Carbon::today();
            $puedeCargar = true; 
            $estadoPlazo = 'DENTRO_PLAZO';
            
            if ($cal && !$antesDeInicio) {
                $hoyStr = Carbon::now()->toDateString();
                $aperturaStr = $cal->fecha_apertura ? $cal->fecha_apertura->format('Y-m-d') : '9999-99-99';
                $isFuture = $hoyStr < $aperturaStr;

                if ($isFuture) {
                    $estadoPlazo = 'FUTURO';
                    $puedeCargar = false; 
                } elseif ($cal->fecha_cierre && $hoy > $cal->fecha_cierre) {
                    if ($cal->fecha_cierre_fuera_plazo && $hoy <= $cal->fecha_cierre_fuera_plazo) {
                        $estadoPlazo = 'FUERA_PLAZO';
                    } else {
                        $estadoPlazo = 'VENCIDO';
                    }
                }
            } else {
                // SI NO HAY CALENDARIO, TRATAR COMO FUTURO/BLOQUEADO PARA CARGA
                $estadoPlazo = 'S/C';
                $puedeCargar = false;
            }

            // Calcular estado real basado en documentos si no está enviado
            $estadoFinal = $carpeta ? $carpeta->estado : 'PENDIENTE';
            if ($carpeta && $carpeta->estado !== 'ENVIADO') {
                if ($carpeta->documentos()->exists()) {
                    $estadoFinal = 'EN PROGRESO';
                }
            }

            $this->periodos[] = [
                'anio' => $this->anio_seleccionado,
                'mes' => $m,
                'nombre_mes' => $this->getNombreMes($m),
                'periodo' => $this->getNombreMes($m) . ' ' . $this->anio_seleccionado,
                'carpeta_id' => $carpeta ? $carpeta->id : null,
                'estado' => $estadoFinal,
                'estado_revision' => $carpeta ? $carpeta->estado_revision : null,
                'estado_plazo' => $estadoPlazo,
                'puede_cargar' => $puedeCargar,
                'fecha_apertura' => ($cal && $cal->fecha_apertura) ? $cal->fecha_apertura->format('d/m/Y') : null,
                'fecha_cierre' => ($cal && $cal->fecha_cierre) ? $cal->fecha_cierre->format('d/m/Y') : null,
                'fecha_cierre_fuera_plazo' => ($cal && $cal->fecha_cierre_fuera_plazo) ? $cal->fecha_cierre_fuera_plazo->format('d/m/Y') : null,
                'fecha_emision' => ($cal && $cal->fecha_emision) ? $cal->fecha_emision->format('d/m/Y') : null,
                'fecha_emision_fuera_plazo' => ($cal && $cal->fecha_emision_fuera_plazo) ? $cal->fecha_emision_fuera_plazo->format('d/m/Y') : null,
                'counts' => $carpeta ? [
                    'observaciones' => (count($carpeta->fin_observaciones_json ?? []) + $carpeta->trabajadoresVerificados()->whereNotNull('observaciones')->count()),
                    'retenibles' => \App\Models\CarpetaTrabajadorContingencia::whereIn('carpeta_verificacion_trabajador_id', $carpeta->trabajadoresVerificados()->pluck('id'))->where('es_retenible', true)->count(),
                    'no_retenibles' => \App\Models\CarpetaTrabajadorContingencia::whereIn('carpeta_verificacion_trabajador_id', $carpeta->trabajadoresVerificados()->pluck('id'))->where('es_retenible', false)->count(),
                ] : null,
                'ia_datos_extraidos' => $carpeta ? $carpeta->ia_datos_extraidos : false,
                'fuera_vigencia' => $fueraVigencia,
                'excluido' => $excluido,
            ];
        }
        
        // Ordenar periodos por mes de NÓMINA descendente (Reciente arriba)
        $this->periodos = collect($this->periodos)->sortByDesc('mes')->values()->toArray();

        // Aplicar Filtro IA si corresponde
        if ($this->filtro_ia) {
            $this->periodos = collect($this->periodos)->filter(function($p) {
                if ($this->filtro_ia === 'IA_OK') return $p['ia_datos_extraidos'] === true;
                if ($this->filtro_ia === 'IA_PENDIENTE') return $p['ia_datos_extraidos'] === false;
                return true;
            })->values()->toArray();
        }

        // Aplicar Filtro Envío
        if ($this->estado_plazo) {
            $this->periodos = collect($this->periodos)->filter(function($p) {
                return $p['estado_plazo'] === $this->estado_plazo;
            })->values()->toArray();
        }
    }

    private function getNombreMes($mes)
    {
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $nombres[$mes];
    }

    private function getNombrePeriodo($mes, $anio)
    {
        // YA NO RESTAR UN MES, el mes que entra YA es el de nómina
        return $this->getNombreMes($mes) . ' ' . $anio;
    }

    public function seleccionarPeriodo($anio, $mes)
    {
        // $mes es el mes de NÓMINA (1-12)
        $this->anio_seleccionado = $anio;
        $this->mes_seleccionado = $mes;

        $vinculacion = ContratistaUnidadOrganizacional::with('unidadOrganizacional.mandante')->find($this->vinculacion_seleccionada_id);
        if (!$vinculacion) return;
        
        // Validación de Seguridad: ¿Es un periodo válido y no excluido?
        // 1. Rango de fechas
        $mesNomina = Carbon::create($anio, $mes, 1);
        $inicioNomina = $mesNomina->copy()->startOfMonth();
        $finNomina = $mesNomina->copy()->endOfMonth();

        // Validación de Seguridad: ¿Es un periodo válido y no excluido?
        // SE RELAJA: Se permite entrar aunque esté fuera de vigencia técnica para cumplir con requerimiento del usuario

        // 2. Exclusión manual
        $fechaCalendarioStr = $mesNomina->copy()->addMonth()->startOfMonth()->toDateString();
        $excluido = ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
            ->where('periodo', $fechaCalendarioStr)
            ->exists();
        if ($excluido) {
            session()->flash('upload_error', 'Este periodo ha sido deshabilitado por el mandante.');
            return;
        }

        // ── MASTER RULE: La carpeta se identifica con el MES DE NÓMINA (Lógica Honesta) ──
        $this->carpeta_actual = CarpetaVerificacion::firstOrCreate([
            'contratista_unidad_organizacional_id' => $this->vinculacion_seleccionada_id,
            'anio' => $anio,
            'mes' => $mes
        ]);

        $this->modal_confirmacion_visible = false;
        $this->declaracion_aceptada = false;
        $this->archivos = [];
    }

    public function abrirModalConfirmacion()
    {
        if (!$this->carpeta_actual) return;

        // Validaciones previas (documentos obligatorios, secuencialidad, etc)
        $faltantes = $this->obtenerDocumentosObligatoriosFaltantes();
        if (!empty($faltantes)) {
            $lista = implode(', ', $faltantes);
            session()->flash('upload_status', "❌ No puede enviar este periodo. Faltan documentos obligatorios: {$lista}.");
            return;
        }

        if ($errorBloqueo = $this->verificarBloqueoSecuencial()) {
            session()->flash('upload_status', $errorBloqueo);
            return;
        }

        $this->declaracion_aceptada = false;
        $this->modal_confirmacion_visible = true;
    }

    public function updatedArchivos($value, $name)
    {
        // El name contiene el requisitoId
        $parts = explode('.', $name);
        if (count($parts) >= 1) {
            $requisitoId = end($parts);
            $this->subirArchivos($requisitoId);
        }
    }

    public function subirArchivos($requisitoId)
    {
        if (!$this->carpeta_actual || $this->carpeta_actual->estado === 'ENVIADO') {
            session()->flash('upload_error', 'Este periodo está bloqueado.');
            return;
        }

        // Verificar si hay archivos para este requisito
        if (!isset($this->archivos[$requisitoId]) || empty($this->archivos[$requisitoId])) {
            return;
        }

        try {
            $this->validate([
                'archivos.' . $requisitoId . '.*' => $this->getFileValidationRule('verificacion'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (isset($this->archivos[$requisitoId])) {
                foreach ($this->archivos[$requisitoId] as $file) {
                    $this->validateSecureFile($file, 'verificacion', 'VERIFICACION');
                }
            }
            throw $e;
        }

        $vinculacion = ContratistaUnidadOrganizacional::with(['contratista', 'unidadOrganizacionalMandante.mandante', 'dependencia'])->find($this->vinculacion_seleccionada_id);

        // ── Principal (Mandante) ──────────────────────────────────────
        $mandanteRaw = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'SIN_PRINCIPAL';
        $mandante = $this->sanitizarParaArchivo($mandanteRaw);

        $idRegistro = $vinculacion->id_registro ?: $vinculacion->contratista->id;

        // ── Lugar de Trabajo = Dependencia ────────────────────────────
        $lugarRaw = $vinculacion->dependencia?->nombre ?? 'SIN_LUGAR';
        $lugar = $this->sanitizarParaArchivo($lugarRaw);

        // ── N° Contrato: sin contrato = 'SC' ──────────────────────────
        $contratoRaw = $vinculacion->numero_contrato ?? 'SC';
        $contrato = $this->sanitizarParaArchivo((string) $contratoRaw);

        // YA NO RESTAR UN MES: carpeta_actual->mes YA ES el de nomina
        $mesPad = str_pad($this->carpeta_actual->mes, 2, '0', STR_PAD_LEFT);
        $periodo = "{$mesPad}_{$this->carpeta_actual->anio}";
        $requisito = RequisitoVerificacion::find($requisitoId);
        $codigoDoc = $requisito && $requisito->codigo ? $requisito->codigo : 'DOC';

        $existingCount = DocumentoVerificacion::where('carpeta_verificacion_id', $this->carpeta_actual->id)
            ->where('requisito_verificacion_id', $requisitoId)
            ->count();

        foreach ($this->archivos[$requisitoId] as $file) {
            $existingCount++;
            $ext = $file->getClientOriginalExtension() ?: 'pdf';
            // FORMATO: PRINCIPAL-ID_REGISTRO-DEPENDENCIA-CONTRATO-MM_AAAA-TIPO-N.PDF
            $nombreLimpio = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$existingCount}.{$ext}");

            // ── ESCUDO CRIPTOGRÁFICO ──────────────────────────────────────
            $storageResult = $this->encryptAndStoreFile($file, 'verificacion/' . $this->carpeta_actual->id, 'VERIFICACION');
            
            DocumentoVerificacion::create([
                'carpeta_verificacion_id' => $this->carpeta_actual->id,
                'requisito_verificacion_id' => $requisitoId,
                'path' => $storageResult['ruta_archivo'],
                'nombre_original' => $nombreLimpio,
                'is_encrypted' => $storageResult['is_encrypted'],
            ]);
        }

        $this->archivos[$requisitoId] = [];
        
        // RECARGA PROFUNDA
        $this->carpeta_actual = CarpetaVerificacion::with('documentos')->find($this->carpeta_actual->id);
        
        // Si no está ENVIADO, actualizar a EN PROGRESO
        if ($this->carpeta_actual->estado !== 'ENVIADO') {
            $this->carpeta_actual->update(['estado' => 'EN PROGRESO']);
        }

        $this->detallarPeriodos($this->vinculacion_seleccionada_id, false);
        session()->flash('upload_status', 'Archivos subidos correctamente.');
    }

    public function eliminarDocumento($docId)
    {
        if (!$this->carpeta_actual || $this->carpeta_actual->estado === 'ENVIADO') {
            return;
        }

        $doc = DocumentoVerificacion::find($docId);
        if ($doc) {
            // Borrado seguro usando el Trait
            $this->deleteDocumentFile($doc->path, (bool)$doc->is_encrypted);
            $doc->delete();
        }
        
        // Recarga profunda
        $this->carpeta_actual = CarpetaVerificacion::with('documentos')->find($this->carpeta_actual->id);
        $this->detallarPeriodos($this->vinculacion_seleccionada_id, false);
    }

    private function obtenerDocumentosObligatoriosFaltantes(): array
    {
        if (!$this->carpeta_actual) return [];

        $vinculacion = ContratistaUnidadOrganizacional::find($this->vinculacion_seleccionada_id);
        if (!$vinculacion) return [];

        $mandanteId = $vinculacion->unidadOrganizacionalMandante->mandante_id ?? $vinculacion->unidadOrganizacional->mandante_id;

        $obligatorios = RequisitoVerificacion::where('mandante_id', $mandanteId)
            ->where('is_active', true)
            ->where('es_obligatorio', true)
            ->get();

        $faltantes = [];
        foreach ($obligatorios as $req) {
            $tieneDoc = DocumentoVerificacion::where('carpeta_verificacion_id', $this->carpeta_actual->id)
                ->where('requisito_verificacion_id', $req->id)
                ->exists();
            if (!$tieneDoc) {
                $faltantes[] = $req->nombre;
            }
        }
        return $faltantes;
    }

    public function enviarPeriodo()
    {
        if (!$this->carpeta_actual) return;

        if (!$this->declaracion_aceptada) {
            session()->flash('upload_status', '❌ Debe aceptar la declaración de veracidad antes de confirmar.');
            return;
        }
        
        // VALIDACIÓN DE DOCUMENTOS OBLIGATORIOS
        $faltantes = $this->obtenerDocumentosObligatoriosFaltantes();
        if (!empty($faltantes)) {
            $lista = implode(', ', $faltantes);
            session()->flash('upload_status', "❌ No puede enviar este periodo. Faltan documentos obligatorios: {$lista}.");
            return;
        }

        // Obtener la vinculación para acceder al mandante
        $vinculacion = ContratistaUnidadOrganizacional::find($this->vinculacion_seleccionada_id);
        if (!$vinculacion) return;

        // VALIDACIÓN FINAL DE SEGURIDAD (Cruce con Informar Contratistas)
        $mesNomina = Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1);
        $fechaCalendarioStr = $mesNomina->copy()->addMonth()->startOfMonth()->toDateString();
        
        $excluido = ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
            ->where('periodo', $fechaCalendarioStr)
            ->exists();
        
        if ($excluido) {
            session()->flash('upload_status', '❌ No puede enviar este periodo: Ha sido deshabilitado por el mandante.');
            return;
        }

        // RESTRICCIÓN CRÍTICA DE SECUENCIALIDAD: NO ENVIAR SI EL PERIODO VÁLIDO ANTERIOR NO ESTÁ EMITIDO
        if ($errorBloqueo = $this->verificarBloqueoSecuencial()) {
            session()->flash('upload_status', $errorBloqueo);
            return;
        }

        // Validación de Rango de Fechas
        
        // Calcular el mes de CALENDARIO (Nomina + 1 mes) para buscar plazos
        $calDate = Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1)->addMonth();
        $mesCarga = $calDate->month;
        $anioCarga = $calDate->year;
        
        // Buscar el calendario correspondiente al codigo de mes de CALENDARIO
        $calendario = CalendarioVerificacion::where('mandante_id', $vinculacion->unidadOrganizacional->mandante_id)
            ->where('anio', $anioCarga)
            ->where('mes', $mesCarga)
            ->first();
        
        $hoy = Carbon::now();
        $tipoEnvio = 'NORMAL';
        $fechaEmisionAsignada = null;
        $mensaje = 'Periodo enviado. Edición bloqueada.';
        
        if ($calendario) {
            // Determinar tipo de envío basado en fechas
            if ($calendario->fecha_cierre && $hoy->lte($calendario->fecha_cierre)) {
                // Dentro de plazo normal
                $tipoEnvio = 'NORMAL';
                $fechaEmisionAsignada = $calendario->fecha_emision;
                $mensaje = '✅ Periodo enviado DENTRO DE PLAZO. Emisión: ' . ($fechaEmisionAsignada ? \Carbon\Carbon::parse($fechaEmisionAsignada)->format('d/m/Y') : 'Pendiente');
            } elseif ($calendario->fecha_cierre_fuera_plazo && $hoy->lte($calendario->fecha_cierre_fuera_plazo)) {
                // Fuera de plazo pero dentro del periodo extraordinario
                $tipoEnvio = 'FUERA_PLAZO';
                $fechaEmisionAsignada = $calendario->fecha_emision_fuera_plazo;
                $mensaje = '⚠️ Periodo enviado FUERA DE PLAZO. Emisión: ' . ($fechaEmisionAsignada ? \Carbon\Carbon::parse($fechaEmisionAsignada)->format('d/m/Y') : 'Próximo periodo');
            } else {
                // Fuera del periodo extraordinario - emitirá en el siguiente periodo
                $tipoEnvio = 'FUERA_PERIODO';
                $fechaEmisionAsignada = null; // Se asignará cuando se procese el siguiente periodo
                $mensaje = '✅ PERIODO ENVIADO (Fuera de ciclo). Se emitirá en la siguiente fecha de cierre mensual.';
            }
        }
        
        $this->carpeta_actual->update([
            'estado' => 'ENVIADO',
            'tipo_envio' => $tipoEnvio,
            'fecha_emision_asignada' => $fechaEmisionAsignada,
            'fecha_envio' => $hoy,
        ]);

        // CONSOLIDACIÓN DE RESERVA: Para cada trabajador en la nómina
        foreach ($this->carpeta_actual->trabajadoresVerificados as $tv) {
            if ($tv->vinculacion) {
                $this->consolidarReserva($tv->vinculacion->trabajador_id);
            }
        }



        $this->modal_confirmacion_visible = false;
        $this->carpeta_actual = CarpetaVerificacion::with('documentos')->find($this->carpeta_actual->id);
        
        $this->detallarPeriodos($this->vinculacion_seleccionada_id, false);
        session()->flash('upload_status', $mensaje);
    }

    public function render()
    {
        $requisitosAgrupados = [];
        $documentosCargados = collect();
        $trabajadoresVinculados = collect();

        if ($this->vinculacion_seleccionada_id && $this->anio_seleccionado && $this->mes_seleccionado) {
            $vinculacion = ContratistaUnidadOrganizacional::find($this->vinculacion_seleccionada_id);
            if ($vinculacion) {
                $requisitosAgrupados = RequisitoVerificacion::with('clasificacion')
                    ->where('mandante_id', $vinculacion->unidadOrganizacional->mandante_id)
                    ->where('is_active', true)
                    ->get()
                    ->sortBy(function($item) {
                        return $item->clasificacion ? $item->clasificacion->orden : 999;
                    })
                    ->groupBy(function($item) {
                        return $item->clasificacion ? $item->clasificacion->nombre : 'OTROS';
                    });
                
                // ── MASTER RULE: La carpeta es la del MES DE NÓMINA seleccionado ──
                $this->carpeta_actual = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
                    ->where('anio', $this->anio_seleccionado)
                    ->where('mes', $this->mes_seleccionado)
                    ->first();

                if ($this->carpeta_actual) {
                    // ── Cargar documentos de la carpeta para mostrarlos en la vista ──
                    $documentosCargados = DocumentoVerificacion::where('carpeta_verificacion_id', $this->carpeta_actual->id)
                        ->get();

                    // Sincronizar nómina (VIGENTES y ARRASTRES)
                    // Solo si la carpeta no ha sido enviada aún
                    if ($this->carpeta_actual->estado !== 'ENVIADO') {
                        $this->inicializarNominaVerificada($this->carpeta_actual);
                    }

                    // Cargar la nómina verificada persistida
                    $trabajadoresVinculados = $this->carpeta_actual->trabajadoresVerificados()
                        ->with(['vinculacion.trabajador', 'vinculacion.cargoMandante', 'destinoVinculacion.unidadOrganizacionalMandante'])
                        ->get();
                } else {
                    $trabajadoresVinculados = collect();
                }
            }
        }

        // Obtener el periodo actual seleccionado desde el array de periodos
        $periodoActual = null;
        if ($this->mes_seleccionado && $this->anio_seleccionado) {
            foreach ($this->periodos as $p) {
                if ($p['mes'] == $this->mes_seleccionado && $p['anio'] == $this->anio_seleccionado) {
                    $periodoActual = $p;
                    break;
                }
            }
        }

        return view('livewire.contratista.verificacion', [
            'requisitos' => $requisitosAgrupados,
            'documentosCargados' => $documentosCargados,
            'periodoActual' => $periodoActual,
            'trabajadoresVinculados' => $trabajadoresVinculados,
        ])->layout('layouts.app');
    }

    public function inicializarNominaVerificada($carpeta)
    {
        // 1. Obtener dotación actual (VIGENTE) según filtros históricos
        $pStart = \Carbon\Carbon::create($carpeta->anio, $carpeta->mes, 1)->startOfMonth();
        $pEnd   = $pStart->copy()->endOfMonth();

        $vigentes = \App\Models\TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $carpeta->vinculacion->unidad_organizacional_mandante_id)
            ->where('dependencia_id', $carpeta->vinculacion->dependencia_id)
            ->where(function($q) use ($carpeta) {
                if ($carpeta->vinculacion->numero_contrato) {
                    $q->where('numero_contrato', $carpeta->vinculacion->numero_contrato);
                }
            })
            ->whereHas('trabajador', function ($q) use ($carpeta) {
                $q->where('contratista_id', $carpeta->vinculacion->contratista_id);
            })
            ->where('fecha_ingreso_vinculacion', '<=', $pEnd)
            ->where(function($sq) use ($pStart) {
                $sq->whereNull('fecha_desactivacion')
                   ->orWhere('fecha_desactivacion', '>=', $pStart);
            })
            ->get();

        foreach ($vigentes as $v) {
            $existe = $carpeta->trabajadoresVerificados()
                ->where('trabajador_vinculacion_id', $v->id)
                ->exists();

            if (!$existe) {
                $carpeta->trabajadoresVerificados()->create([
                    'trabajador_vinculacion_id' => $v->id,
                    'tipo_registro'             => 'VIGENTE',
                    'estado_revision'           => in_array($v->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']) ? $v->motivo_desactivacion : 'PENDIENTE',
                    'snapshot_rut'              => $v->trabajador->rut,
                    'snapshot_nombres'          => $v->trabajador->nombre_completo,
                    'snapshot_cargo'            => $v->cargoMandante?->nombre_cargo ?? 'CARGO NO REGISTRADO',
                    'snapshot_fecha_ingreso'    => $v->fecha_ingreso_vinculacion,
                    'snapshot_fecha_contrato'   => $v->fecha_contrato,
                ]);
            }
        }

        // 2. Buscar último periodo verificado
        $ultimaCarpeta = \App\Models\CarpetaVerificacion::where('contratista_unidad_organizacional_id', $carpeta->contratista_unidad_organizacional_id)
            ->where('id', '!=', $carpeta->id)
            ->where(function($q) use ($carpeta) {
                $q->where('anio', '<', $carpeta->anio)
                  ->orWhere(function($sq) use ($carpeta) {
                      $sq->where('anio', $carpeta->anio)
                         ->where('mes', '<', $carpeta->mes);
                  });
            })
            ->whereIn('estado_revision', ['REVISADO', 'AUDITADO'])
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->with('trabajadoresVerificados')
            ->first();

        if ($ultimaCarpeta) {
            $baseAnterior = $ultimaCarpeta->trabajadoresVerificados()
                ->whereNotIn('estado_revision', ['FINIQUITADO', 'MOVIDO'])
                ->get();

            foreach ($baseAnterior as $tAnt) {
                // Si no está ya en la carpeta (ni como VIGENTE ni como ARRASTRE)
                $yaEnCarpeta = $carpeta->trabajadoresVerificados()
                    ->where('trabajador_vinculacion_id', $tAnt->trabajador_vinculacion_id)
                    ->exists();

                if (!$yaEnCarpeta) {
                    $carpeta->trabajadoresVerificados()->create([
                        'trabajador_vinculacion_id' => $tAnt->trabajador_vinculacion_id,
                        'tipo_registro'             => 'ARRASTRE',
                        'estado_revision'           => 'PENDIENTE',
                        'snapshot_rut'              => $tAnt->snapshot_rut ?: ($tAnt->vinculacion?->trabajador?->rut),
                        'snapshot_nombres'          => $tAnt->snapshot_nombres ?: ($tAnt->vinculacion?->trabajador?->nombre_completo),
                        'snapshot_cargo'            => $tAnt->snapshot_cargo ?: ($tAnt->vinculacion?->cargoMandante?->nombre_cargo ?? 'CARGO NO REGISTRADO'),
                        'snapshot_fecha_ingreso'    => $tAnt->snapshot_fecha_ingreso ?: ($tAnt->vinculacion?->fecha_ingreso_vinculacion),
                        'snapshot_fecha_contrato'   => $tAnt->snapshot_fecha_contrato ?: ($tAnt->vinculacion?->fecha_contrato),
                    ]);
                }
            }
        }
    }

    public function cambiarEstadoTrabajadorPeriodo($id, $nuevoEstado, $destinoId = null)
    {
        $reg = \App\Models\CarpetaVerificacionTrabajador::with(['vinculacion.trabajador', 'carpeta'])->find($id);
        if (!$reg) return;

        $estadoPrevio = $reg->estado_revision;

        // PRESENTE_OTRA_VINCULACION: si no tiene otras vinculaciones activas, forzar a ACTIVO
        if ($nuevoEstado === 'PRESENTE_OTRA_VINCULACION') {
            $destinos = $this->getDestinosPosibles($reg->trabajador_vinculacion_id);
            if ($destinos->isEmpty()) {
                $reg->update([
                    'estado_revision' => 'PENDIENTE',
                    'destino_trabajador_vinculacion_id' => null,
                ]);
                $this->dispatch('notify', [
                    'type'    => 'warning',
                    'message' => 'El trabajador no tiene otras vinculaciones activas. Estado revertido a ACTIVO.',
                ]);
                return;
            }
        }

        $reg->update([
            'estado_revision'                   => $nuevoEstado,
            'destino_trabajador_vinculacion_id' => ($nuevoEstado === 'PRESENTE_OTRA_VINCULACION') ? $destinoId : null,
        ]);

        // ── ESTADOS DE DESVINCULACIÓN: propagar a todas las nóminas del mismo período + desactivar vinculaciones ──
        if (in_array($nuevoEstado, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])) {
            $this->propagarDesvinculacion($reg, $nuevoEstado);
        }

        // ── REVERSIÓN: si venía de un estado de desvinculación y vuelve a ACTIVO, reactivar vinculaciones ──
        if (in_array($estadoPrevio, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']) && $nuevoEstado === 'PENDIENTE') {
            $this->revertirDesvinculacion($reg, $estadoPrevio);
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Estado del trabajador actualizado.']);
    }

    /**
     * Cuando se marca FINIQUITADO:
     * 1. Propaga FINIQUITADO a todas las nóminas del mismo período (todas las vinculaciones del trabajador).
     * 2. Desactiva TODAS las TrabajadorVinculacion del trabajador → no aparece en períodos futuros.
     * 3. El registro Trabajador se conserva ("en reserva").
    /**
     * Cuando se marca un estado de desvinculación:
     * 1. Propaga el estado a todas las nóminas del mismo período (todas las vinculaciones del trabajador).
     * 2. Desactiva TODAS las TrabajadorVinculacion del trabajador → no aparece en períodos futuros.
     * 3. El registro Trabajador se conserva ("en reserva").
     */
    private function propagarDesvinculacion(\App\Models\CarpetaVerificacionTrabajador $reg, string $nuevoEstado): void
    {
        $trabajadorId = $reg->vinculacion->trabajador_id ?? null;
        if (!$trabajadorId) return;

        // Todas las vinculaciones del trabajador
        $vinculacionIds = \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)->pluck('id');

        // Carpetas del MISMO período (mismo anio + mes), excluyendo las ya EMITIDAS
        $carpetasSamePeriod = \App\Models\CarpetaVerificacion::where('anio', $reg->carpeta->anio)
            ->where('mes', $reg->carpeta->mes)
            ->where(function ($q) {
                $q->whereNull('estado_revision')
                  ->orWhere('estado_revision', '!=', 'EMITIDO');
            })
            ->pluck('id');

        // 1. Marcar el nuevo estado en todas las nóminas del período
        \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculacionIds)
            ->whereIn('carpeta_verificacion_id', $carpetasSamePeriod)
            ->where('id', '!=', $reg->id) // el registro actual ya fue actualizado
            ->update(['estado_revision' => $nuevoEstado]);

        // 2. Desactivar TODAS las TrabajadorVinculacion del trabajador
        $fechaFin = $reg->vinculacion->fecha_finiquito
            ? \Carbon\Carbon::parse($reg->vinculacion->fecha_finiquito)->toDateString()
            : now()->toDateString();

        \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
            ->update([
                'is_active'           => false,
                'fecha_desactivacion' => $fechaFin,
                'fecha_finiquito'     => $fechaFin,
                'motivo_desactivacion'=> $nuevoEstado,
            ]);
    }

    /**
     * Reversión: si el contratista cambia de un estado de desvinculación → ACTIVO,
     * reactivar las vinculaciones y desmarcar el estado en el mismo período.
     */
    private function revertirDesvinculacion(\App\Models\CarpetaVerificacionTrabajador $reg, string $estadoPrevio): void
    {
        $trabajadorId = $reg->vinculacion->trabajador_id ?? null;
        if (!$trabajadorId) return;

        $vinculacionIds = \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)->pluck('id');

        // Reactivar solo las vinculaciones que nosotros desactivamos
        \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
            ->where('motivo_desactivacion', $estadoPrevio)
            ->update([
                'is_active'            => true,
                'fecha_desactivacion'  => null,
                'motivo_desactivacion' => null,
            ]);

        // Revertir el estado → PENDIENTE en las nóminas del mismo período (no EMITIDAS)
        $carpetasSamePeriod = \App\Models\CarpetaVerificacion::where('anio', $reg->carpeta->anio)
            ->where('mes', $reg->carpeta->mes)
            ->where(function ($q) {
                $q->whereNull('estado_revision')
                  ->orWhere('estado_revision', '!=', 'EMITIDO');
            })
            ->pluck('id');

        \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculacionIds)
            ->whereIn('carpeta_verificacion_id', $carpetasSamePeriod)
            ->where('id', '!=', $reg->id)
            ->where('estado_revision', $estadoPrevio)
            ->update(['estado_revision' => 'PENDIENTE']);
    }

    public function actualizarFechaFiniquito($id, $fecha)
    {
        // Aplica para: FINIQUITADO, CESACION_PRINCIPAL, RECONOCIMIENTO_ANTIGUEDAD
        $reg = \App\Models\CarpetaVerificacionTrabajador::with('vinculacion.trabajador')->find($id);
        if ($reg && $reg->vinculacion) {
            $reg->vinculacion->update([
                'fecha_finiquito' => $fecha ?: null
            ]);

            // Si el trabajador fue finiquitado (o cesado / con antiguedad reconocida), actualizar también fecha en TODAS sus vinculaciones
            if (in_array($reg->estado_revision, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']) && $reg->vinculacion->trabajador_id) {
                \App\Models\TrabajadorVinculacion::where('trabajador_id', $reg->vinculacion->trabajador_id)
                    ->where('motivo_desactivacion', $reg->estado_revision)
                    ->update([
                        'fecha_finiquito'     => $fecha ?: null,
                        'fecha_desactivacion' => $fecha ? \Carbon\Carbon::parse($fecha)->toDateString() : null
                    ]);
            }

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Fecha actualizada correctamente.']);
        }
    }

    public function getDestinosPosibles($trabajadorVinculacionId)
    {
        $vinculacionOrigen = \App\Models\TrabajadorVinculacion::find($trabajadorVinculacionId);
        if (!$vinculacionOrigen) return collect();

        // Buscar otras vinculaciones activas del mismo trabajador para el mismo contratista
        return \App\Models\TrabajadorVinculacion::with(['unidadOrganizacionalMandante', 'dependencia'])
            ->where('trabajador_id', $vinculacionOrigen->trabajador_id)
            ->where('id', '!=', $trabajadorVinculacionId)
            ->where('is_active', true)
            ->get();
    }

    private function sanitizarParaArchivo(string $valor): string
    {
        // Tabla de reemplazos de caracteres acentuados y especiales del español
        $from = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','à','è','ì','ò','ù','â','ê','î','ô','û','ç','Ç'];
        $to   = ['a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','a','e','i','o','u','c','C'];
        $valor = str_replace($from, $to, $valor);

        // Eliminar cualquier carácter que no sea letra, número, guión o punto
        $valor = preg_replace('/[^A-Za-z0-9\-]/', '_', $valor);

        // Colapsar guiones bajos múltiples en uno solo
        $valor = preg_replace('/_+/', '_', $valor);

        // Quitar guiones bajos al inicio o al final
        $valor = trim($valor, '_');

        return strtoupper($valor);
    }

    /**
     * Saneamiento Global Agresivo: Asegura que un trabajador sólo tenga
     * registros útiles en la tabla operacional.
     */
    private function consolidarReserva($trabajadorId)
    {
        $vinculaciones = \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)->get();
        if ($vinculaciones->isEmpty()) return;

        $tieneActivas = $vinculaciones->where('is_active', true)->isNotEmpty();
        
        // REGLA MAESTRA: Solo limpiamos campos si el trabajador NO tiene nada activo
        // Y si no tiene ninguna nómina pendiente de envío (abierta).
        if (!$tieneActivas) {
            $tieneNominasAbiertas = \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculaciones->pluck('id'))
                ->whereHas('carpeta', function ($q) {
                    $q->where('estado', '!=', 'ENVIADO');
                })
                ->exists();

            if (!$tieneNominasAbiertas) {
                // El trabajador está en RESERVA TOTAL. Limpiamos campos para el Maestro.
                foreach ($vinculaciones as $v) {
                    $v->update([
                        'unidad_organizacional_mandante_id' => null,
                        'dependencia_id' => null,
                        'numero_contrato' => null,
                        'is_active' => false
                    ]);
                }
                // NO ELIMINAMOS registros físicamente para no romper el rastro en carpetas pasadas.
            }
        }
    }
}
