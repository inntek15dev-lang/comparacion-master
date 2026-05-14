<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Imports\DotacionAnteriorImport;
use App\Exports\DotacionAnteriorTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class ImportarDotacionAnterior extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $file;
    public $mandante_id;
    public $filtro_contratista_id;
    public $filtro_periodo;
    
    public $mandantes = [];
    public $contratistas = [];
    
    public bool $importing      = false;
    public bool $importFinished = false;
    public array $importResults = [];

    public function mount()
    {
        $this->mandantes = \App\Models\Mandante::where('is_active', true)->orderBy('razon_social')->get();
        // Cargamos todas las contratistas al entrar
        $this->contratistas = \App\Models\Contratista::orderBy('razon_social')->get();
    }

    public function downloadTemplate()
    {
        $this->validate([
            'mandante_id'    => 'required',
            'filtro_periodo' => ['nullable', 'regex:/^\d{4}-\d{1,2}$/']
        ], [
            'mandante_id.required'    => 'Debe seleccionar un Principal/Mandante para generar la plantilla correctamente.',
            'filtro_periodo.regex'    => 'El formato del Período debe ser YYYY-MM.',
        ]);

        return Excel::download(
            new DotacionAnteriorTemplateExport(
                $this->mandante_id,
                $this->filtro_contratista_id,
                $this->filtro_periodo
            ),
            'plantilla_dotacion_anterior.xlsx'
        );
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_DOTACION_ANTERIOR');
            throw $e;
        }

        $this->importing      = true;
        $this->importFinished = false;
        $this->importResults  = [];

        try {
            DotacionAnteriorImport::clearCache();

            $import = new DotacionAnteriorImport();
            Excel::import($import, $this->file->getRealPath());

            $this->importResults = [
                'activos'      => $import->activos,
                'nuevos'       => $import->nuevos,
                'finiquitados' => $import->finiquitados,
                'movidos'      => $import->movidos ?? 0,
                'omitidos'     => $import->omitidos,
                'failures'     => $import->failures,
                'failure_count'=> count($import->failures),
            ];

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $this->importResults = [
                'activos'      => 0,
                'nuevos'       => 0,
                'finiquitados' => 0,
                'movidos'      => 0,
                'omitidos'     => 0,
                'failure_count'=> count($failures),
                'failures'     => array_map(fn($f) => [
                    'row'       => $f->row(),
                    'attribute' => $f->attribute(),
                    'errors'    => implode(', ', $f->errors()),
                    'values'    => $f->values(),
                ], $failures),
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Error inesperado durante la importación: ' . $e->getMessage());
            Log::error('Error en importación de dotación anterior: ' . $e->getMessage());
            $this->resetImport();
            return;
        }

        $this->importing      = false;
        $this->importFinished = true;
        $this->reset('file');
    }

    public function resetImport()
    {
        $this->reset(['file', 'importing', 'importFinished', 'importResults']);
        // No reseteamos mandante_id ni mandantes
    }

    public function render()
    {
        return view('livewire.importar-dotacion-anterior');
    }
}
