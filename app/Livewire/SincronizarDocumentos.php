<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Exports\SincronizacionTemplateExport;
use App\Imports\SincronizacionDocumentosImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class SincronizarDocumentos extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $file;
    public $importing      = false;
    public $importFinished = false;
    public $importResults  = [];
    public $totalArchivosFisicos = 0;
    public $carpetaIngesta = 'importar_documentos_sincronizacion';

    public $mandante_id;
    public $mandantes = [];

    protected $listeners = ['actualizarConteoEventSync' => 'actualizarConteoArchivos'];

    public function mount()
    {
        $this->actualizarConteoArchivos();

        $user = auth()->user();
        if ($user->hasRole('Mandante_Admin')) {
            $this->mandantes   = \App\Models\Mandante::where('id', $user->mandante_id)->get();
            $this->mandante_id = $user->mandante_id;
        } else {
            $this->mandantes = \App\Models\Mandante::where('is_active', true)->orderBy('razon_social')->get();
        }
    }

    public function actualizarConteoArchivos()
    {
        if (!Storage::disk('public')->exists($this->carpetaIngesta)) {
            Storage::disk('public')->makeDirectory($this->carpetaIngesta);
        }
        $this->totalArchivosFisicos = count(Storage::disk('public')->files($this->carpetaIngesta));
    }

    public function downloadTemplate()
    {
        if (!$this->mandante_id) {
            session()->flash('error', 'Debe seleccionar un Principal para generar la plantilla.');
            return;
        }

        return Excel::download(
            new SincronizacionTemplateExport($this->mandante_id),
            'plantilla_sincronizacion_documentos.xlsx'
        );
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'SINCRONIZAR_DOCUMENTOS');
            throw $e;
        }

        $this->importing     = true;
        $this->importFinished = false;
        $this->importResults  = [
            'vivos'       => 0,
            'archivados'  => 0,
            'descartados' => 0,
            'failures'    => [],
        ];

        if ($this->totalArchivosFisicos === 0) {
            session()->flash('error', 'No se encontraron archivos PDFs en la carpeta de sincronización. Suba los archivos físicos primero.');
            $this->importing = false;
            return;
        }

        try {
            $import = new SincronizacionDocumentosImport();
            Excel::import($import, $this->file);

            $this->importResults = [
                'vivos'       => $import->vivos,
                'archivados'  => $import->archivados,
                'descartados' => $import->descartados,
                'failures'    => $import->failures,
            ];

            $totalOk = $import->vivos + $import->descartados;
            if ($totalOk > 0) {
                session()->flash('message', "Sincronización completada: {$import->vivos} documentos activos, {$import->archivados} documentos viejos archivados, {$import->descartados} entrantes descartados (el existente era mejor).");

                \App\Services\AuditService::log(
                    'SYNC_DOCUMENTS',
                    "Sincronización desde sistema obsoleto: {$import->vivos} activos, {$import->archivados} archivados, {$import->descartados} descartados",
                    [
                        'vivos'       => $import->vivos,
                        'archivados'  => $import->archivados,
                        'descartados' => $import->descartados,
                        'failures'    => count($import->failures),
                    ]
                );
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error durante la sincronización: ' . $e->getMessage());
        }

        $this->importing     = false;
        $this->importFinished = true;
        $this->reset('file');
    }

    public function resetImport()
    {
        $this->reset(['file', 'importing', 'importFinished', 'importResults']);
        $this->actualizarConteoArchivos();
    }

    public function clearTemporalFolder()
    {
        $files = Storage::disk('public')->files($this->carpetaIngesta);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
        $this->actualizarConteoArchivos();
        session()->flash('message', 'Carpeta de sincronización vaciada correctamente.');
    }

    public function render()
    {
        return view('livewire.sincronizar-documentos')
            ->layout('layouts.app');
    }
}
