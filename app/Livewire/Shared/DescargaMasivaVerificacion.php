<?php

namespace App\Livewire\Shared;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\RequisitoVerificacion;
use App\Models\DocumentoVerificacion;
use App\Models\CarpetaVerificacion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DescargaMasivaVerificacion extends Component
{
    // MODO: 'documentos' o 'certificados'
    public $modo_descarga = 'documentos';

    // Filtros principales
    public $mandante_id = '';
    public $requisito_id = '';

    // Filtros adicionales (Nuevos)
    public $lugar_id = '';       // Dependencia
    public $contrato_id = '';    // numero_contrato
    public $contratista_id = ''; // contratista_id

    // Selector de tipo de filtro secundario
    // Opciones: 'periodo', 'rango_fecha', 'plazo'
    public $tipo_filtro = 'periodo';

    // Para tipo_filtro = periodo
    public $anio = '';
    public $mes  = '';

    // Para tipo_filtro = rango_fecha
    public $fecha_desde = '';
    public $fecha_hasta = '';

    // Para tipo_filtro = plazo
    public $tipo_envio = '';

    // Preview
    public $preview_documentos = 0;
    public $preview_contratistas = 0;
    public $preview_listo = false;

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function updatingMandanteId()
    {
        $this->reset(['requisito_id', 'lugar_id', 'contrato_id', 'contratista_id']);
    }

    public function updatingLugarId()
    {
        $this->reset(['contrato_id', 'contratista_id']);
    }

    public function updatingModoDescarga()
    {
        $this->reset(['requisito_id']);
    }

    public function updatingTipoFiltro()
    {
        // Limpiar variables de otros filtros
        if ($this->tipo_filtro !== 'periodo') {
            $this->anio = '';
            $this->mes = '';
        } else {
            $this->anio = date('Y');
        }

        if ($this->tipo_filtro !== 'rango_fecha') {
            $this->fecha_desde = '';
            $this->fecha_hasta = '';
        }

        if ($this->tipo_filtro !== 'plazo') {
            $this->tipo_envio = '';
        }
    }

    public function generarDescarga()
    {
        // Validaciones básicas
        $rules = [
            'mandante_id' => 'required',
        ];
        
        if ($this->modo_descarga === 'documentos') {
            $rules['requisito_id'] = 'required';
        }

        $this->validate($rules, [
            'mandante_id.required' => 'Debe seleccionar una Principal (Mandante).',
            'requisito_id.required' => 'Debe seleccionar un Tipo de Documento.',
        ]);

        if ($this->tipo_filtro === 'periodo') {
            $this->validate(['anio' => 'required', 'mes' => 'required'], ['anio.required' => 'Debe seleccionar un año.', 'mes.required' => 'Debe seleccionar un mes.']);
        } elseif ($this->tipo_filtro === 'rango_fecha') {
            $this->validate(['fecha_desde' => 'required', 'fecha_hasta' => 'required'], ['fecha_desde.required' => 'Debe informar fecha de inicio.', 'fecha_hasta.required' => 'Debe informar fecha final.']);
        } elseif ($this->tipo_filtro === 'plazo') {
            $this->validate(['tipo_envio' => 'required'], ['tipo_envio.required' => 'Debe seleccionar dentro o fuera de plazo.']);
        }

        // AUDITORÍA: Registrar la acción de descarga masiva
        \App\Services\AuditService::log(
            'DOWNLOAD_MASSIVE',
            "Generó descarga masiva de " . ($this->modo_descarga === 'certificados' ? 'CERTIFICADOS' : 'DOCUMENTOS'),
            [
                'modo' => $this->modo_descarga,
                'mandante_id' => $this->mandante_id,
                'requisito_id' => $this->requisito_id,
            ]
        );

        // REGLA: Se excluyen SOLO las carpetas en estado 'PENDIENTE' (período aún no enviado
        // por el contratista). Todos los demás estados (ENVIADO, y cualquier estado_revision
        // como REVISADO, PARA_EMITIR, EMITIDO) deben incluirse en la descarga masiva.
        //
        // NOTA SQL: mandante_id NO existe como columna directa en 'contratista_unidad_organizacional'.
        // Se filtra por mandante a través de la UO (si existe) o bien de la Dependencia.
        $mandanteId = $this->mandante_id;
        $carpetasQuery = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
            ->whereHas('vinculacion', function ($q) use ($mandanteId) {
                $q->where(function ($sub) use ($mandanteId) {
                    $sub->whereHas('unidadOrganizacionalMandante', fn ($q2) => $q2->where('mandante_id', $mandanteId))
                        ->orWhereHas('dependencia', fn ($q2) => $q2->where('mandante_id', $mandanteId));
                });

                // Filtros adicionales (Nuevos)
                if ($this->lugar_id) {
                    $q->where('dependencia_id', $this->lugar_id);
                }
                if ($this->contrato_id) {
                    $q->where('numero_contrato', $this->contrato_id);
                }
                if ($this->contratista_id) {
                    $q->where('contratista_id', $this->contratista_id);
                }
            });

        if ($this->modo_descarga === 'certificados') {
            $carpetasQuery->where('estado_revision', 'EMITIDO');
        }

        // Aplicar filtro secundario
        if ($this->tipo_filtro === 'periodo') {
            // El usuario selecciona el mes de nómina en la UI (ej. Noviembre = 11)
            // La carpeta YA se guarda en el mes de nomina (11)
            $carpetasQuery->where('anio', $this->anio)->where('mes', $this->mes);
        } elseif ($this->tipo_filtro === 'rango_fecha') {
            // fecha_envio es datetime (comprobando por rangos)
            // Agregamos horas para cubrir el día completo
            $desde = $this->fecha_desde . ' 00:00:00';
            $hasta = $this->fecha_hasta . ' 23:59:59';
            $carpetasQuery->whereBetween('fecha_envio', [$desde, $hasta]);
        } elseif ($this->tipo_filtro === 'plazo') {
            if ($this->tipo_envio == 'NORMAL') {
                $carpetasQuery->where('tipo_envio', 'NORMAL');
            } else {
                // Fuera de plazo o periodo
                $carpetasQuery->whereIn('tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']);
            }
        }

        $carpetasId = $carpetasQuery->pluck('id');

        if ($this->modo_descarga === 'documentos') {
            // Buscar los documentos — se cargan Mandante, Dependencia para el nombre del archivo ZIP
            $documentos = DocumentoVerificacion::with([
                'carpeta.vinculacion.contratista',
                'carpeta.vinculacion.dependencia',
                'carpeta.vinculacion.unidadOrganizacionalMandante.mandante',
            ])
                ->whereIn('carpeta_verificacion_id', $carpetasId)
                ->where('requisito_verificacion_id', $this->requisito_id)
                ->whereNotNull('path')
                ->get();

            if ($documentos->isEmpty()) {
                session()->flash('warning', 'No se encontraron documentos para los filtros seleccionados.');
                return;
            }
        } else {
            // MODO CERTIFICADOS
            $carpetas = $carpetasQuery->with([
                'vinculacion.contratista',
                'vinculacion.dependencia',
                'vinculacion.unidadOrganizacionalMandante.mandante'
            ])->get();

            if ($carpetas->isEmpty()) {
                session()->flash('warning', 'No se encontraron certificados emitidos para los filtros seleccionados.');
                return;
            }
        }

        // Crear ZIP
        $zip = new ZipArchive();
        $zipFileName = 'descarga_masiva_verificacion_' . date('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $archivosAgregados = 0;
            
            // Obtener el código del requisito
            $requisito = RequisitoVerificacion::find($this->requisito_id);
            $codigoDoc = $requisito && $requisito->codigo ? $requisito->codigo : 'DOC';

            $contadorArchivos = []; // Para el sufijo -X

            if ($this->modo_descarga === 'documentos') {
                foreach ($documentos as $doc) {
                    $realPath = Storage::disk('local')->path($doc->path);
                    if (!file_exists($realPath)) {
                        $realPath = Storage::disk('public')->path($doc->path);
                    }

                    if (file_exists($realPath)) {
                        if (!$doc->carpeta || !$doc->carpeta->vinculacion || !$doc->carpeta->vinculacion->contratista) {
                            continue;
                        }

                        $contratista  = $doc->carpeta->vinculacion->contratista;
                        $carpeta      = $doc->carpeta;
                        $vinculacion  = $carpeta->vinculacion;

                        $idRegistro = $vinculacion->id_registro ?: $contratista->id;
                        $mandanteRaw = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'SIN_PRINCIPAL';
                        $mandante = $this->sanitizarParaArchivo($mandanteRaw);
                        $lugarRaw = $vinculacion->dependencia?->nombre ?? 'SIN_LUGAR';
                        $lugar = $this->sanitizarParaArchivo($lugarRaw);
                        $contratoRaw = $vinculacion->numero_contrato ?: 'SC';
                        $contrato = $this->sanitizarParaArchivo((string) $contratoRaw);
                        $mesPad = str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT);
                        $periodo = "{$mesPad}_{$carpeta->anio}";

                        $keyContador = "{$vinculacion->id}_{$periodo}";
                        if (!isset($contadorArchivos[$keyContador])) {
                            $contadorArchivos[$keyContador] = 1;
                        } else {
                            $contadorArchivos[$keyContador]++;
                        }
                        $x = $contadorArchivos[$keyContador];

                        $ext = pathinfo($realPath, PATHINFO_EXTENSION);
                        if (!$ext && $doc->nombre_original) {
                            $ext = pathinfo($doc->nombre_original, PATHINFO_EXTENSION);
                        }
                        $ext = $ext ? strtoupper($ext) : 'PDF';

                        if ($doc->is_encrypted) {
                            try {
                                $contenido = app(\App\Services\EncryptionService::class)->decryptToMemory($doc->path);
                                $nombreLimpio = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$x}.PDF");
                                $zip->addFromString($nombreLimpio, $contenido);
                                $archivosAgregados++;
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Error al desencriptar documento en DescargaMasivaVerificacion: " . $e->getMessage());
                            }
                        } else {
                            $nombreLimpio = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$x}.{$ext}");
                            $zip->addFile($realPath, $nombreLimpio);
                            $archivosAgregados++;
                        }
                    }
                }
            } else {
                // MODO CERTIFICADOS: Generar PDF sobre la marcha
                foreach ($carpetas as $carpeta) {
                    $vinculacion = $carpeta->vinculacion;
                    $contratista = $vinculacion->contratista;
                    
                    // Lógica del CertificadoController
                    $carpetaFull = CarpetaVerificacion::with([
                        'vinculacion.contratista',
                        'vinculacion.unidadOrganizacional.mandante',
                        'vinculacion.dependencia',
                        'analista',
                        'supervisor',
                        'auditor',
                        'trabajadoresVerificados.vinculacion.trabajador',
                        'trabajadoresVerificados.contingencias.catalogoItem',
                        'trabajadoresVerificados.contingencias.itemsComplementarios.solicitud'
                    ])->find($carpeta->id);

                    // Agrupación de contingencias (reutilizando lógica de CertificadoController)
                    $contingenciasAgrupadas = $this->_agruparContingencias($carpetaFull);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificados.certificado-auditoria', [
                        'carpeta'                => $carpetaFull,
                        'contingenciasAgrupadas' => $contingenciasAgrupadas,
                        'trabajadores'           => $carpetaFull->trabajadoresVerificados,
                    ]);
                    $pdf->setPaper('letter', 'portrait');
                    $pdfContent = $pdf->output();

                    // ── Nombre final exigido ──────────────────────────────────────────
                    // PRINCIPAL_LUGAR_CONTRATO_ID_REGISTRO_PERIODO.PDF
                    $mandanteRaw = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'SIN_PRINCIPAL';
                    $mandante = $this->sanitizarParaArchivo($mandanteRaw);
                    $lugarRaw = $vinculacion->dependencia?->nombre ?? 'SIN_LUGAR';
                    $lugar = $this->sanitizarParaArchivo($lugarRaw);
                    $contratoRaw = $vinculacion->numero_contrato ?: 'SC';
                    $contrato = $this->sanitizarParaArchivo((string) $contratoRaw);
                    $idRegistro = $vinculacion->id_registro ?: $contratista->id;
                    $mesPad = str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT);
                    $periodo = "{$mesPad}_{$carpeta->anio}";

                    $nombreArchivo = strtoupper("{$mandante}_{$lugar}_{$contrato}_{$idRegistro}_{$periodo}.PDF");
                    
                    $zip->addFromString($nombreArchivo, $pdfContent);
                    $archivosAgregados++;
                }
            }

            $zip->close();

            if ($archivosAgregados === 0) {
                // Se borra el zip vacío
                @unlink($zipPath);
                session()->flash('error', 'Los archivos referenciados no se encuentran físicamente en el servidor.');
                return;
            }

            // Forzar descarga del ZIP
            return response()->download($zipPath)->deleteFileAfterSend(true);
        } else {
            session()->flash('error', 'No se pudo crear el archivo ZIP.');
            return;
        }
    }

    private function calcularPreview()
    {
        $this->preview_documentos = 0;
        $this->preview_contratistas = 0;
        $this->preview_listo = false;

        if (!$this->mandante_id) {
            return;
        }
        
        if ($this->modo_descarga === 'documentos' && !$this->requisito_id) {
            return;
        }

        if ($this->tipo_filtro === 'periodo' && (!$this->anio || !$this->mes)) return;
        if ($this->tipo_filtro === 'rango_fecha' && (!$this->fecha_desde || !$this->fecha_hasta)) return;
        if ($this->tipo_filtro === 'plazo' && !$this->tipo_envio) return;

        // Mismo criterio: excluir solo 'PENDIENTE'. Filtrar mandante vía UO o Dependencia.
        $mandanteId = $this->mandante_id;
        $carpetasQuery = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
            ->whereHas('vinculacion', function ($q) use ($mandanteId) {
                $q->where(function ($sub) use ($mandanteId) {
                    $sub->whereHas('unidadOrganizacionalMandante', fn ($q2) => $q2->where('mandante_id', $mandanteId))
                        ->orWhereHas('dependencia', fn ($q2) => $q2->where('mandante_id', $mandanteId));
                });

                // Filtros adicionales (Nuevos)
                if ($this->lugar_id) {
                    $q->where('dependencia_id', $this->lugar_id);
                }
                if ($this->contrato_id) {
                    $q->where('numero_contrato', $this->contrato_id);
                }
                if ($this->contratista_id) {
                    $q->where('contratista_id', $this->contratista_id);
                }
            });

        if ($this->modo_descarga === 'certificados') {
            $carpetasQuery->where('estado_revision', 'EMITIDO');
        }

        if ($this->tipo_filtro === 'periodo') {
            $carpetasQuery->where('anio', $this->anio)->where('mes', $this->mes);
        } elseif ($this->tipo_filtro === 'rango_fecha') {
            $desde = $this->fecha_desde . ' 00:00:00';
            $hasta = $this->fecha_hasta . ' 23:59:59';
            $carpetasQuery->whereBetween('fecha_envio', [$desde, $hasta]);
        } elseif ($this->tipo_filtro === 'plazo') {
            if ($this->tipo_envio == 'NORMAL') {
                $carpetasQuery->where('tipo_envio', 'NORMAL');
            } else {
                $carpetasQuery->whereIn('tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']);
            }
        }

        $carpetasId = $carpetasQuery->pluck('id');

        if ($this->modo_descarga === 'documentos') {
            $documentos = DocumentoVerificacion::with('carpeta.vinculacion')
                ->whereIn('carpeta_verificacion_id', $carpetasId)
                ->where('requisito_verificacion_id', $this->requisito_id)
                ->whereNotNull('path')
                ->get()
                ->filter(function ($doc) {
                    return $doc->carpeta && $doc->carpeta->vinculacion;
                });

            $this->preview_documentos = $documentos->count();
            
            // Count distinct contractors using id_registro (fallback to contratista_id)
            $this->preview_contratistas = $documentos->map(function($doc) {
                $v = $doc->carpeta->vinculacion;
                return $v->id_registro ?: $v->contratista_id;
            })->filter()->unique()->count();
        } else {
            $this->preview_documentos = $carpetasQuery->count();
            $this->preview_contratistas = $carpetasQuery->get()->map(function($c) {
                return $c->vinculacion->contratista_id;
            })->unique()->count();
        }
        
        $this->preview_listo = true;
    }

    public function render()
    {
        $this->calcularPreview();
        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();

        $requisitos = collect();
        $lugares = collect();
        $contratos = collect();
        $contratistas = collect();

        if ($this->mandante_id) {
            $requisitos = RequisitoVerificacion::with('clasificacion')
                ->where('mandante_id', $this->mandante_id)
                ->where('is_active', true)
                ->orderBy('nombre')
                ->get();
            
            // Cargar dependencias (Lugares de Trabajo)
            $lugares = \App\Models\Dependencia::where('mandante_id', $this->mandante_id)
                ->where('estado', true)
                ->orderBy('nombre')
                ->get();

            // Cargar Contratos y Contratistas basados en lo seleccionado
            $vQuery = \App\Models\ContratistaUnidadOrganizacional::whereHas('unidadOrganizacionalMandante', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            });

            if ($this->lugar_id) {
                $vQuery->where('dependencia_id', $this->lugar_id);
            }

            $vinculaciones = $vQuery->with('contratista')->get();
            
            $contratos = $vinculaciones->pluck('numero_contrato')->filter()->unique()->sort();
            $contratistas = $vinculaciones->pluck('contratista')->filter()->unique('id')->sortBy('razon_social');
        }

        return view('livewire.shared.descarga-masiva-verificacion', [
            'mandantes' => $mandantes,
            'requisitos' => $requisitos,
            'lugares' => $lugares,
            'contratos' => $contratos,
            'contratistas' => $contratistas,
        ])->layout('layouts.app');
    }

    /**
     * Reutiliza lógica del CertificadoController para agrupar contingencias.
     */
    private function _agruparContingencias($carpeta)
    {
        $contingenciasAgrupadas = [];
        $trabajadores = $carpeta->trabajadoresVerificados;

        foreach ($trabajadores as $ctv) {
            foreach ($ctv->contingencias as $contingencia) {
                if ($contingencia->tipo !== 'contingencia' || $contingencia->subtipo !== 'retenible') {
                    continue;
                }

                $textoAGrupar = $contingencia->catalogo_item_id
                    ? ($contingencia->catalogoItem->texto_plural ?? $contingencia->causal)
                    : $contingencia->causal;

                $clasif = $contingencia->clasificacion ?? 'Sin Clasificación';
                $claveAgrupamiento = $textoAGrupar . '|' . $clasif;

                if (!isset($contingenciasAgrupadas[$claveAgrupamiento])) {
                    $contingenciasAgrupadas[$claveAgrupamiento] = [
                        'texto_plural'   => $textoAGrupar,
                        'texto_singular' => $contingencia->causal,
                        'clasificacion'  => $clasif,
                        'codigo'         => $contingencia->codigo,
                        'afectados'      => [],
                        'monto_solucionado' => 0,
                        'fecha_solucion'    => null,
                        'observaciones_solucion' => null,
                    ];
                }

                $montoAcumuladoSolucionado = 0;
                $ultimaFechaSolucion = null;
                $ultimaObs = null;

                foreach ($contingencia->itemsComplementarios as $itemSc) {
                    if (in_array($itemSc->estado_auditor, ['TOTAL', 'PARCIAL'])) {
                        if ($itemSc->estado_auditor === 'TOTAL') {
                            $montoAcumuladoSolucionado = $contingencia->monto; 
                        } else {
                            $montoAcumuladoSolucionado += $itemSc->monto_solucionado;
                        }

                        $sol = $itemSc->solicitud;
                        if ($sol) {
                            if (!$ultimaFechaSolucion || $sol->fecha_revision > $ultimaFechaSolucion) {
                                $ultimaFechaSolucion = $sol->fecha_revision;
                                $ultimaObs = $sol->observaciones_auditor;
                            }
                        }
                    }
                }

                if ($montoAcumuladoSolucionado > 0) {
                    $contingenciasAgrupadas[$claveAgrupamiento]['monto_solucionado'] += $montoAcumuladoSolucionado;
                    if ($ultimaFechaSolucion && (!$contingenciasAgrupadas[$claveAgrupamiento]['fecha_solucion'] || $ultimaFechaSolucion > $contingenciasAgrupadas[$claveAgrupamiento]['fecha_solucion'])) {
                        $contingenciasAgrupadas[$claveAgrupamiento]['fecha_solucion'] = $ultimaFechaSolucion;
                        $contingenciasAgrupadas[$claveAgrupamiento]['observaciones_solucion'] = $ultimaObs;
                    }
                }

                $contingenciasAgrupadas[$claveAgrupamiento]['afectados'][] = [
                    'trabajador' => $ctv->vinculacion->trabajador,
                    'monto'      => $contingencia->monto,
                    'codigo'     => $contingencia->codigo,
                    'estado_subsanacion' => $contingencia->estado_subsanacion,
                    'monto_solucionado'  => $montoAcumuladoSolucionado,
                ];
            }
        }
        return $contingenciasAgrupadas;
    }

    /**
     * Convierte un string a formato seguro para nombre de archivo.
     * - Elimina acentos y diacríticos (á→A, ñ→N, ü→U, etc.)
     * - Reemplaza espacios y caracteres especiales por guión bajo
     * - Colapsa múltiples guiones bajos consecutivos
     * - Convierte a MAYÚSCULAS
     *
     * Ejemplo: "Faena Norte Río" → "FAENA_NORTE_RIO"
     */
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
}
