<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\ContratistasImport;
use App\Exports\ContratistasTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Traits\ValidatesFileUpload;

#[Layout('layouts.app')]
class ImportarContratistas extends Component
{
    use WithFileUploads, ValidatesFileUpload;

    public $file;
    public $importing = false;
    public $importFinished = false;
    public array $importResults = [];

    // Propiedades para Exportación
    public string $exportMode = 'todos';
    public ?int $exportMandanteId = null;
    public ?int $exportContratistaId = null;
    public array $mandantesParaExport = [];
    public array $contratistasParaExport = [];

    public function mount()
    {
        $this->mandantesParaExport = Mandante::where('is_active', true)
            ->orderBy('razon_social')
            ->get()
            ->toArray();
    }

    public function updatedExportMandanteId($value)
    {
        $this->exportContratistaId = null;
        $this->contratistasParaExport = [];

        if ($value) {
            $this->contratistasParaExport = Contratista::whereHas('solicitudesVinculacion', function ($q) use ($value) {
                $q->where('mandante_id', $value)
                  ->where('tipo_solicitud', 'CONTRATISTA')
                  ->where('estado', 'APROBADA');
            })->orderBy('razon_social')->get()->toArray();
        }
    }

    public function downloadTemplate()
    {
        $this->validate([
            'exportMandanteId' => 'required',
        ], [
            'exportMandanteId.required' => 'Debe seleccionar un Mandante (Principal) para exportar.',
        ]);

        if ($this->exportMode === 'uno' && empty($this->exportContratistaId)) {
            session()->flash('error', 'Debe seleccionar un Contratista Específico.');
            return;
        }

        $contratistaId = ($this->exportMode === 'uno') ? $this->exportContratistaId : null;
        return Excel::download(new ContratistasTemplateExport($this->exportMandanteId, $contratistaId), 'plantilla_contratistas.xlsx');
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_CONTRATISTAS');
            throw $e;
        }

        $this->importing = true;
        $this->importFinished = false;
        $this->importResults = [];

        try {
            // ================== INICIO DE LA MODIFICACIÓN ==================
            $import = new ContratistasImport(); // Ya no necesita el ID del usuario
            // ================== FIN DE LA MODIFICACIÓN ====================
            Excel::import($import, $this->file->getRealPath());

            $this->importResults = [
                'success_count' => $import->successes,
                'failure_count' => count($import->failures),
                'failures' => $import->failures,
                'passwords' => $import->passwords,
            ];

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $this->importResults = [
                'success_count' => 0,
                'failure_count' => count($failures),
                'failures' => $failures,
                'passwords' => [],
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado durante la importación: ' . $e->getMessage());
            Log::error('Error en importación de contratistas: ' . $e->getMessage());
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
        return view('livewire.importar-contratistas');
    }
}