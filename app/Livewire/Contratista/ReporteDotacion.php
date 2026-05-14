<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Mandante;
use App\Services\FacturacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\FacturacionMensualExport;
use App\Exports\FacturacionDetalleExport;
use App\Exports\FacturacionResumenExport;

class ReporteDotacion extends Component
{
    public $mandantesDisponibles;
    public $mandanteId = '';
    public ?string $fechaDesde = null;
    public ?string $fechaHasta = null;

    public $resultados = [];
    public $showModalDetalle = false;
    public $detalleContratista = null;
    public $detalleTrabajadores = [];

    protected $facturacionService;

    public function boot(FacturacionService $facturacionService)
    {
        $this->facturacionService = $facturacionService;
    }

    public function mount()
    {
        $contratistaId = Auth::user()->contratista_id;

        $mandanteIds = DB::table('solicitudes_vinculacion')
            ->where('contratista_id', $contratistaId)
            ->where('estado', 'APROBADA')
            ->pluck('mandante_id')
            ->unique();

        $this->mandantesDisponibles = Mandante::whereIn('id', $mandanteIds)
            ->where('is_active', true)
            ->orderBy('razon_social')
            ->get();

        // Si solo hay un mandante, lo seleccionamos por defecto.
        if ($this->mandantesDisponibles->count() === 1) {
            $this->mandanteId = $this->mandantesDisponibles->first()->id;
        }

        $this->fechaDesde = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['mandanteId', 'fechaDesde', 'fechaHasta'])) {
            $this->resultados = [];
        }
    }

    public function generarReporte()
    {
        $this->validate([
            'mandanteId' => 'required|exists:mandantes,id',
            'fechaDesde' => 'required|date',
            'fechaHasta' => 'required|date|after_or_equal:fechaDesde',
        ], [
            'mandanteId.required' => 'Debe seleccionar un Principal.',
            'fechaDesde.required' => 'La fecha de inicio es obligatoria.',
            'fechaHasta.required' => 'La fecha de fin es obligatoria.',
            'fechaHasta.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ]);

        $contratistaId = Auth::user()->contratista_id;

        $this->resultados = $this->facturacionService->calcularFacturacionPorPeriodo(
            $this->mandanteId,
            $this->fechaDesde,
            $this->fechaHasta,
            $contratistaId // Forzamos el ID del contratista logueado
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

        \App\Services\AuditService::securityAlert(
            "Exportación de Reporte de Dotación Completo ({$formato})",
            "EXPORTE_MASIVO",
            ['formato' => $formato, 'mandante_id' => $this->mandanteId]
        );

        $mandanteNombre = Mandante::find($this->mandanteId)->razon_social;
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Reporte_Dotacion_{$mandanteNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

        $datosParaExportar = [
            'mandanteNombre' => $mandanteNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'resumen' => $this->resultados['resumen'],
            'detalle' => $this->resultados['detalle'],
            'totalGeneral' => $this->resultados['total_general'],
            'showMandanteColumn' => false, // El contratista siempre ve el contexto de un mandante
        ];

        if ($formato === 'excel') {
            return Excel::download(new FacturacionMensualExport($datosParaExportar), "{$nombreArchivo}.xlsx");
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('exports.facturacion-mensual', ['datos' => $datosParaExportar])->setPaper('a4', 'landscape');
            return response()->streamDownload(fn() => print($pdf->output()), "{$nombreArchivo}.pdf");
        }
    }

    public function exportarResumenSolo($formato)
    {
        if (empty($this->resultados) || $this->resultados['total_general'] === 0) {
            session()->flash('error', 'No hay datos para exportar. Por favor, genere un reporte primero.');
            return;
        }

        \App\Services\AuditService::securityAlert(
            "Exportación de Resumen de Dotación ({$formato})",
            "EXPORTE_MASIVO",
            ['formato' => $formato, 'mandante_id' => $this->mandanteId]
        );

        $mandanteNombre = Mandante::find($this->mandanteId)->razon_social;
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Resumen_Dotacion_{$mandanteNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

        $datosParaExportar = [
            'mandanteNombre' => $mandanteNombre,
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
            'resumen' => $this->resultados['resumen'],
            'totalGeneral' => $this->resultados['total_general'],
            'showMandanteColumn' => false,
        ];

        if ($formato === 'excel') {
            return Excel::download(new FacturacionResumenExport($datosParaExportar), "{$nombreArchivo}.xlsx");
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('exports.facturacion-mensual-resumen-solo', ['datos' => $datosParaExportar]);
            return response()->streamDownload(fn() => print($pdf->output()), "{$nombreArchivo}.pdf");
        }
    }

    public function exportarDetalle($formato)
    {
        if (!$this->detalleContratista || empty($this->detalleTrabajadores)) {
            session()->flash('error', 'No hay datos de detalle para exportar.');
            return;
        }

        \App\Services\AuditService::securityAlert(
            "Exportación de Detalle de Dotación Individual ({$formato})",
            "EXPORTE_MASIVO",
            [
                'formato' => $formato, 
                'contratista_id' => $this->detalleContratista->contratista_id ?? 'N/A'
            ]
        );

        $mandanteNombre = $this->detalleContratista->mandante_nombre;
        $contratistaNombre = str_replace(' ', '_', $this->detalleContratista->razon_social);
        $timestamp = now()->format('Y-m-d_His');
        $nombreArchivo = "Detalle_Dotacion_{$contratistaNombre}_{$this->fechaDesde}_a_{$this->fechaHasta}_{$timestamp}";

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
            return response()->streamDownload(fn() => print($pdf->output()), "{$nombreArchivo}.pdf");
        }
    }

    public function render()
    {
        return view('livewire.contratista.reporte-dotacion')->layout('layouts.app');
    }
}