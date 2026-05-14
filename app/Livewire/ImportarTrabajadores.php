<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\TrabajadoresImport;
use App\Exports\TrabajadoresTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use App\Models\Mandante;
use App\Models\Contratista;

#[Layout('layouts.app')]
class ImportarTrabajadores extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $file;
    public $importing = false;
    public $importFinished = false;
    public array $importResults = [];

    // Propiedades para la descarga de plantilla dinámica
    public $mandante_id = '';
    public $contratista_id = '';
    public $mandantes = [];
    public $contratistas = [];

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
    }

    public function updatedMandanteId($value)
    {
        $this->contratista_id = '';
        $this->contratistas   = [];

        if ($value) {
            $mandante = Mandante::find($value);
            if ($mandante) {
                $this->contratistas = $mandante->contratistasPrincipalesAprobados()->orderBy('razon_social')->get();
            }
        }
    }

    public function downloadTemplate()
    {
        $this->validate([
            'mandante_id'    => 'required',
            'contratista_id' => 'required', // 'todas' es un valor válido (no vacío)
        ], [
            'mandante_id.required'    => 'Debe seleccionar un Principal.',
            'contratista_id.required' => 'Debe seleccionar un Contratista (o "TODAS").',
        ]);

        // Si el usuario eligió "TODAS", pasamos null para exportar todos los contratistas del mandante
        $contratistaId = ($this->contratista_id === 'todas') ? null : $this->contratista_id;

        return Excel::download(
            new TrabajadoresTemplateExport($this->mandante_id, $contratistaId),
            'plantilla_trabajadores.xlsx'
        );
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_TRABAJADORES');
            throw $e;
        }

        $this->importing = true;
        $this->importFinished = false;
        $this->importResults = [];

        try {
            $import = new TrabajadoresImport();
            Excel::import($import, $this->file->getRealPath());

            $this->importResults = [
                'success_count' => $import->successes,
                'update_count'  => $import->updates,
                'warning_count' => count($import->warnings),
                'warnings'      => $import->warnings,
                'failure_count' => count($import->failures),
                'failures'      => $import->failures,
            ];

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $this->importResults = [
                'success_count' => 0,
                'failure_count' => count($failures),
                'failures' => $failures,
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado durante la importación: ' . $e->getMessage());
            Log::error('Error en importación de trabajadores: ' . $e->getMessage());
            $this->resetImport();
            return;
        }

        $this->importing = false;
        $this->importFinished = true;
        $this->reset('file');
    }

    public function resetImport()
    {
        $this->reset(['file', 'importing', 'importFinished', 'importResults']);
    }

    public function render()
    {
        return view('livewire.importar-trabajadores');
    }
}