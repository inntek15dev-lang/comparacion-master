<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\VehiculosImport;
use App\Exports\VehiculosTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ImportarVehiculos extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $file;
    public bool $importing = false;
    public bool $importFinished = false;
    public array $importResults = [];

    public function downloadTemplate()
    {
        return Excel::download(new VehiculosTemplateExport, 'plantilla_vehiculos.xlsx');
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_VEHICULOS');
            throw $e;
        }

        $this->importing = true;
        $this->importFinished = false;
        $this->importResults = [];

        try {
            $import = new VehiculosImport;
            Excel::import($import, $this->file);

            $this->importResults = [
                'success_count' => $import->successes,
                'failure_count' => count($import->failures),
                'failures' => $import->failures,
            ];

        } catch (Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado durante el proceso de importación: ' . $e->getMessage());
        }

        $this->importing = false;
        $this->importFinished = true;
    }

    public function resetImport()
    {
        $this->reset(['file', 'importing', 'importFinished', 'importResults']);
    }

    public function render()
    {
        return view('livewire.importar-vehiculos')
            ->layout('layouts.app');
    }
}