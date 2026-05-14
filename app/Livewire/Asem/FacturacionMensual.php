<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Services\FacturacionService;
use Carbon\Carbon;
use App\Exports\FacturacionMensualExport;
use App\Exports\FacturacionDetalleExport;
use App\Exports\FacturacionResumenExport; // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class FacturacionMensual extends Component
{
    public $mandantes;
    public $mandanteId = '';
    public ?string $fechaDesde = null;
    public ?string $fechaHasta = null;

    public $contratistasDisponibles = [];
    public ?int $contratistaId = null;

    public $resultados = [];
    public $showModalDetalle = false;
    public $detalleContratista = null;
    public $detalleTrabajadores = [];

    public bool $showMandanteColumn = false;

    protected $facturacionService;

    public function boot(FacturacionService $facturacionService)
    {
        $this->facturacionService = $facturacionService;
    }

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->fechaDesde = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        $this->updatedMandanteId($this->mandanteId);
    }

    public function updated($propertyName)
    {
        $filtros = ['mandanteId', 'contratistaId', 'fechaDesde', 'fechaHasta'];
        if (in_array($propertyName, $filtros)) {
            $this->resultados = [];
        }
    }

    public function updatedMandanteId($mandanteId)
    {
        $this->contratistaId = null;
        $this->showMandanteColumn = empty($mandanteId);

        if ($mandanteId) {
            $contratistaIds = DB::table('solicitudes_vinculacion')
                ->where('mandante_id', $mandanteId)
                ->where('estado', 'APROBADA')
                ->pluck('contratista_id')
                ->unique();
            
            $this->contratistasDisponibles = Contratista::whereIn('id', $contratistaIds)
                ->orderBy('razon_social')
                ->get();
        } else {
            $this->contratistasDisponibles = Contratista::where('is_active', true)
                ->orderBy('razon_social')
                ->get();
        }
    }

    public function generarReporte()
    {
        $this->validate([
            'mandanteId' => 'present',
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
            'contratistaId' => 'nullable|exists:contratistas,id',
        ], [
            'fechaDesde.required' => 'La fecha de inicio es obligatoria.',
            'fechaHasta.required' => 'La fecha de fin es obligatoria.',
            'fechaHasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ]);

        $this->resultados = $this->facturacionService->calcularFacturacionPorPeriodo(
            $this->mandanteId ?: null,
            $this->fechaDesde,
            $this->fechaHasta,
            $this->contratistaId
        );
    }

    public function abrirModalDetalle($contratistaId, $mandanteId)
    {
        $detalleKey = $contratistaId . '-' . $mandanteId;
        $resumenContratista = collect($this->resultados['resumen'])->firstWhere('contratista_id', $contratistaId);
        $trabajadores = collect($this->resultados['detalle'])->get($detalleKey);

        if ($resumenContratista && $trabajadores) {
            $this->detalleContratista = $resumenContratista;
            $this->detalleTrabajadores = $trabajadores;
            $this->showModalDetalle = true;
        }
    }

    public function cerrarModalDetalle()
    {
        $this->showModalDetalle = false;
        $this->detalleContratista = null;
        $this->detalleTrabajadores = [];
    }

    public function exportar($formato)
    {
        if (empty($this->resultados) || $this->resultados['total_general'] === 0) {
            session()->flash('error', 'No hay datos para exportar. Por favor, genere un reporte primero.');
            return;
        }

        $mandanteNombre = $this->mandanteId ? Mandante::find($this->mandanteId)->razon_social : 'TODAS';
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Reporte_Completo_{$mandanteNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

        $datosParaExportar = [
            'mandanteNombre' => $mandanteNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'resumen' => $this->resultados['resumen'],
            'detalle' => $this->resultados['detalle'],
            'totalGeneral' => $this->resultados['total_general'],
            'showMandanteColumn' => $this->showMandanteColumn,
        ];

        if ($formato === 'excel') {
            return Excel::download(new FacturacionMensualExport($datosParaExportar), "{$nombreArchivo}.xlsx");
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('exports.facturacion-mensual', ['datos' => $datosParaExportar])->setPaper('a4', 'landscape');
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "{$nombreArchivo}.pdf");
        }
    }

    // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
    public function exportarResumenSolo($formato)
    {
        if (empty($this->resultados) || $this->resultados['total_general'] === 0) {
            session()->flash('error', 'No hay datos para exportar. Por favor, genere un reporte primero.');
            return;
        }

        $mandanteNombre = $this->mandanteId ? Mandante::find($this->mandanteId)->razon_social : 'TODAS';
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Resumen_Facturacion_{$mandanteNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

        $datosParaExportar = [
            'mandanteNombre' => $mandanteNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'resumen' => $this->resultados['resumen'],
            'totalGeneral' => $this->resultados['total_general'],
            'showMandanteColumn' => $this->showMandanteColumn,
        ];

        if ($formato === 'excel') {
            return Excel::download(new FacturacionResumenExport($datosParaExportar), "{$nombreArchivo}.xlsx");
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('exports.facturacion-mensual-resumen-solo', ['datos' => $datosParaExportar]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "{$nombreArchivo}.pdf");
        }
    }
    // ================== FIN DE LA MODIFICACIÓN CANÓNICA ==================

    public function exportarDetalle($formato)
    {
        if (!$this->detalleContratista || empty($this->detalleTrabajadores)) {
            session()->flash('error', 'No hay datos de detalle para exportar.');
            return;
        }

        $mandanteNombre = $this->detalleContratista->mandante_nombre;
        $contratistaNombre = str_replace(' ', '_', $this->detalleContratista->razon_social);
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Detalle_{$contratistaNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

        $datosParaExportar = [
            'mandanteNombre' => $mandanteNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'contratista' => $this->detalleContratista,
            'trabajadores' => $this->detalleTrabajadores,
        ];

        if ($formato === 'excel') {
            return Excel::download(new FacturacionDetalleExport($datosParaExportar), "{$nombreArchivo}.xlsx");
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('exports.facturacion-mensual-detalle-individual', ['datos' => $datosParaExportar]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "{$nombreArchivo}.pdf");
        }
    }

    public function render()
    {
        return view('livewire.asem.facturacion-mensual')->layout('layouts.app');
    }
}