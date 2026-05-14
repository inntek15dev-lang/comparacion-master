<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Imports\VerificacionesHistoricasImport;
use App\Exports\VerificacionesHistoricasTemplateExport;
use App\Services\SnapshotDotacionService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class ImportarVerificacionesHistoricas extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    // ── Estado ───────────────────────────────────────────────────────────────
    public $file;
    public bool $importing         = false;
    public bool $importFinished    = false;
    public bool $snapshotEjecutado = false;
    public array $importResults    = [];
    public array $snapshotResults  = [];

    // ── Preview del snapshot ──────────────────────────────────────────────────
    public bool  $mostrarPreviewSnapshot = false;
    public array $previewSnapshot        = [];

    // ─── Descarga de plantilla ────────────────────────────────────────────────
    public function downloadTemplate()
    {
        return Excel::download(
            new VerificacionesHistoricasTemplateExport(),
            'plantilla_verificaciones_historicas.xlsx'
        );
    }

    // ─── Importación ─────────────────────────────────────────────────────────
    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_VERIFICACIONES_HISTORICAS');
            throw $e;
        }

        $this->importing      = true;
        $this->importFinished = false;
        $this->importResults  = [];
        $this->mostrarPreviewSnapshot = false;
        $this->previewSnapshot = [];

        try {
            // Limpiar caché estático entre ejecuciones
            VerificacionesHistoricasImport::clearCache();

            $import = new VerificacionesHistoricasImport();
            Excel::import($import, $this->file->getRealPath());

            $this->importResults = [
                'success_count' => $import->successes,
                'updated_count' => $import->updated,
                'failure_count' => count($import->failures),
                'failures'      => $import->failures,
            ];

            // Si hubo éxitos o actualizaciones, preparar preview del snapshot
            if ($import->successes > 0 || $import->updated > 0) {
                $service = new SnapshotDotacionService();
                $this->previewSnapshot      = $service->preview();
                $this->mostrarPreviewSnapshot = true;
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $this->importResults = [
                'success_count' => 0,
                'updated_count' => 0,
                'failure_count' => count($failures),
                'failures'      => array_map(fn($f) => [
                    'row'       => $f->row(),
                    'attribute' => $f->attribute(),
                    'errors'    => implode(', ', $f->errors()),
                    'values'    => $f->values(),
                ], $failures),
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Error inesperado durante la importación: ' . $e->getMessage());
            Log::error('Error en importación de verificaciones históricas: ' . $e->getMessage());
            $this->resetImport();
            return;
        }

        $this->importing   = false;
        $this->importFinished = true;
        $this->reset('file');
    }

    // ─── Ejecución del Snapshot ───────────────────────────────────────────────
    public function ejecutarSnapshot()
    {
        try {
            $service = new SnapshotDotacionService();
            $service->ejecutar();
            $this->snapshotResults  = $service->resumen;
            $this->snapshotEjecutado = true;
            $this->mostrarPreviewSnapshot = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error ejecutando el snapshot: ' . $e->getMessage());
            Log::error('Error en SnapshotDotacionService: ' . $e->getMessage());
        }
    }

    // ─── Reset ────────────────────────────────────────────────────────────────
    public function resetImport()
    {
        $this->reset([
            'file', 'importing', 'importFinished', 'importResults',
            'snapshotEjecutado', 'snapshotResults',
            'mostrarPreviewSnapshot', 'previewSnapshot',
        ]);
    }

    public function render()
    {
        return view('livewire.importar-verificaciones-historicas');
    }
}
