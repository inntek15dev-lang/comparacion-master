<?php

namespace App\Livewire\Admin;

use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\TrabajadorVinculacion;
use App\Models\Vinculacion;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DescargarPlantillaHistorica extends Component
{
    public $mandantes = [];
    public $contratistas = [];
    
    public $mandante_id = '';
    public $contratista_id = '';
    public $mes = '';
    public $anio = '';
    public $unidad_organizacional = '';
    public $lugar_trabajo = '';

    public function mount()
    {
        $this->mandantes = Mandante::orderBy('razon_social')->get();
        $this->mes = now()->month;
        $this->anio = now()->year;
    }

    public function updatedMandanteId($value)
    {
        $this->contratista_id = '';
        if ($value) {
            // Cargar contratistas asociados a este mandante
            $uoIds = \App\Models\UnidadOrganizacionalMandante::where('mandante_id', $value)->pluck('id');
            $contratistaIds = \App\Models\ContratistaUnidadOrganizacional::whereIn('unidad_organizacional_mandante_id', $uoIds)->pluck('contratista_id');
            
            $this->contratistas = Contratista::whereIn('id', $contratistaIds)->orderBy('razon_social')->get();
        } else {
            $this->contratistas = [];
        }
    }

    public function descargar()
    {
        $this->validate([
            'mandante_id' => 'required',
            'mes' => 'required|numeric|min:1|max:12',
            'anio' => 'required|numeric|min:2000|max:2100',
        ]);

        $mandante = Mandante::find($this->mandante_id);
        if (!$mandante) return;

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=plantilla_historicos_' . $this->mes . '_' . $this->anio . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        // Construir la consulta de trabajadores respetando el esquema real
        $query = DB::table('trabajadores')
            ->join('trabajador_vinculaciones', 'trabajadores.id', '=', 'trabajador_vinculaciones.trabajador_id')
            ->join('contratistas', 'trabajadores.contratista_id', '=', 'contratistas.id')
            ->leftJoin('unidades_organizacionales_mandante', 'trabajador_vinculaciones.unidad_organizacional_mandante_id', '=', 'unidades_organizacionales_mandante.id')
            ->leftJoin('dependencias', 'trabajador_vinculaciones.dependencia_id', '=', 'dependencias.id')
            ->where('unidades_organizacionales_mandante.mandante_id', $this->mandante_id)
            ->where('trabajador_vinculaciones.is_active', true)
            ->select(
                'contratistas.rut as rut_contratista',
                'contratistas.razon_social as razon_social_contratista',
                'unidades_organizacionales_mandante.nombre_unidad as unidad_organizacional',
                'dependencias.nombre as lugar_trabajo',
                'trabajador_vinculaciones.numero_contrato',
                'trabajadores.rut as rut_trabajador',
                'trabajadores.nombres',
                'trabajadores.apellido_paterno',
                'trabajadores.apellido_materno'
            );

        if ($this->contratista_id && $this->contratista_id !== 'TODOS') {
            $query->where('contratistas.id', $this->contratista_id);
        }

        $trabajadores = $query->get();

        // Obtener las contingencias si es que el certificado ya existe en ese periodo
        $contingenciasExistentes = DB::table('carpeta_trabajador_contingencias')
            ->join('carpetas_verificacion_trabajadores', 'carpeta_trabajador_contingencias.carpeta_verificacion_trabajador_id', '=', 'carpetas_verificacion_trabajadores.id')
            ->join('carpetas_verificacion', 'carpetas_verificacion_trabajadores.carpeta_verificacion_id', '=', 'carpetas_verificacion.id')
            ->join('contratista_unidad_organizacional', 'carpetas_verificacion.contratista_unidad_organizacional_id', '=', 'contratista_unidad_organizacional.id')
            ->join('unidades_organizacionales_mandante', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id', '=', 'unidades_organizacionales_mandante.id')
            ->leftJoin('solicitud_complementaria_items', 'carpeta_trabajador_contingencias.id', '=', 'solicitud_complementaria_items.carpeta_trabajador_contingencia_id')
            ->where('unidades_organizacionales_mandante.mandante_id', $this->mandante_id)
            ->where('carpetas_verificacion.mes', $this->mes)
            ->where('carpetas_verificacion.anio', $this->anio);

        if ($this->contratista_id && $this->contratista_id !== 'TODOS') {
            $contingenciasExistentes->where('contratista_unidad_organizacional.contratista_id', $this->contratista_id);
        }

        $contingenciasAgrupadas = $contingenciasExistentes->select(
            'carpetas_verificacion_trabajadores.snapshot_rut as rut_trabajador',
            'carpeta_trabajador_contingencias.clasificacion',
            'carpeta_trabajador_contingencias.causal',
            'carpeta_trabajador_contingencias.monto as monto_adeudado',
            'carpeta_trabajador_contingencias.estado_subsanacion',
            'solicitud_complementaria_items.monto_solucionado',
            'solicitud_complementaria_items.created_at as fecha_solucion'
        )->get()->groupBy('rut_trabajador');

        // Si no hay trabajadores, crear un array con un registro dummy vacío
        if ($trabajadores->isEmpty()) {
            $contratista = null;
            if ($this->contratista_id && $this->contratista_id !== 'TODOS') {
                $contratista = Contratista::find($this->contratista_id);
            }

            $trabajadores = [
                (object) [
                    'rut_contratista' => $contratista->rut ?? '',
                    'razon_social_contratista' => $contratista->razon_social ?? '',
                    'unidad_organizacional' => $this->unidad_organizacional ?: 'LANDES U.O.',
                    'lugar_trabajo' => $this->lugar_trabajo ?: 'LANDES LUGAR',
                    'numero_contrato' => '',
                    'rut_trabajador' => '11.111.111-1',
                    'nombres' => 'TRABAJADOR',
                    'apellido_paterno' => 'EJEMPLO',
                    'apellido_materno' => ''
                ]
            ];
        }

        $mes  = $this->mes;
        $anio = $this->anio;
        $rutMandante = $mandante->rut;

        $callback = function() use ($trabajadores, $contingenciasAgrupadas, $mes, $anio, $rutMandante) {
            $file = fopen('php://output', 'w');
            
            // BOM para Excel (UTF-8)
            fputs($file, "\xEF\xBB\xBF");

            $columnas = [
                'mes_periodo', 'anio_periodo', 'rut_contratista', 'razon_social_contratista', 
                'rut_mandante', 'unidad_organizacional', 'lugar_trabajo', 'numero_contrato',
                'rut_trabajador', 'nombre_trabajador', 
                'contingencia_clasificacion', 'contingencia_causal', 'monto_adeudado', 
                'solucionado', 'monto_solucionado', 'fecha_solucion'
            ];
            fputcsv($file, $columnas, ';');

            foreach ($trabajadores as $trab) {
                $nombreCompleto = trim(($trab->nombres ?? '') . ' ' . ($trab->apellido_paterno ?? '') . ' ' . ($trab->apellido_materno ?? ''));
                $contingencias = $contingenciasAgrupadas->get($trab->rut_trabajador, collect());

                if ($contingencias->count() > 0) {
                    foreach ($contingencias as $cont) {
                        fputcsv($file, [
                            $mes,
                            $anio,
                            $trab->rut_contratista,
                            $trab->razon_social_contratista,
                            $rutMandante,
                            $trab->unidad_organizacional,
                            $trab->lugar_trabajo,
                            $trab->numero_contrato,
                            $trab->rut_trabajador,
                            $nombreCompleto,
                            $cont->clasificacion,
                            $cont->causal,
                            (int)$cont->monto_adeudado,
                            $cont->estado_subsanacion === 'SUBSANADO' ? 'SI' : 'NO',
                            (int)$cont->monto_solucionado,
                            $cont->fecha_solucion ? date('Y-m-d', strtotime($cont->fecha_solucion)) : ''
                        ], ';');
                    }
                } else {
                    fputcsv($file, [
                        $mes,
                        $anio,
                        $trab->rut_contratista,
                        $trab->razon_social_contratista,
                        $rutMandante,
                        $trab->unidad_organizacional,
                        $trab->lugar_trabajo,
                        $trab->numero_contrato,
                        $trab->rut_trabajador,
                        $nombreCompleto,
                        '', '', '', '', '', ''
                    ], ';');
                }
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'plantilla_historicos_' . $this->mes . '_' . $this->anio . '.csv', $headers);
    }

    public function render()
    {
        return view('livewire.admin.descargar-plantilla-historica');
    }
}
