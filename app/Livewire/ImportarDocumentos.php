<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Exports\DocumentosTemplateExport;
use App\Imports\DocumentosImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportarDocumentos extends Component
{
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public $file;
    public $importing = false;
    public $importFinished = false;
    public $importResults = [];
    public $totalArchivosFisicos = 0;
    public $carpetaIngesta = 'importar_documentos_fisicos';

    public $mandante_id;
    public $contratista_id;
    public $regla_documental_id;

    public $mandantes = [];
    public $contratistas = [];
    public $reglas = [];

    protected $listeners = ['actualizarConteoEvent' => 'actualizarConteoArchivos'];

    public function mount()
    {
        $this->actualizarConteoArchivos();
        
        // Obtener mandantes disponibles (limitados si es Mandante_Admin)
        $user = auth()->user();
        if ($user->hasRole('Mandante_Admin')) {
            $this->mandantes = \App\Models\Mandante::where('id', $user->mandante_id)->get();
            $this->mandante_id = $user->mandante_id;
            $this->updatedMandanteId();
        } else {
            $this->mandantes = \App\Models\Mandante::all();
        }
    }

    public function updatedMandanteId()
    {
        $this->contratista_id = null;
        $this->regla_documental_id = null;
        $this->contratistas = [];
        $this->reglas = [];

        if ($this->mandante_id) {
            // Obtener los contratistas que tienen una CUO con este mandante, haciendo join con la tabla UO
            $cuos = \App\Models\ContratistaUnidadOrganizacional::select('contratista_unidad_organizacional.contratista_id', 'contratista_unidad_organizacional.id_registro')
                ->join('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id')
                ->where('uo.mandante_id', $this->mandante_id)
                ->get()
                ->keyBy('contratista_id');

            $contratistaIds = $cuos->keys();

            $this->contratistas = \App\Models\Contratista::whereIn('id', $contratistaIds)
            ->orderBy('razon_social')
            ->get()
            ->map(function ($contratista) use ($cuos) {
                $idRegistro = $cuos[$contratista->id]->id_registro ?? null;
                $display = $idRegistro 
                    ? "{$idRegistro}" 
                    : "{$contratista->razon_social} (Sin ID_REGISTRO ⚠️)";
                    
                return [
                    'id' => $contratista->id,
                    'display' => $display
                ];
            });
            $this->reglas = \App\Models\ReglaDocumental::with('nombreDocumento')
                ->where('mandante_id', $this->mandante_id)
                ->where('is_active', true)
                ->get()
                ->sortBy(function ($regla) {
                    return $regla->nombreDocumento->nombre ?? '';
                });
        }
    }

    public function updatedContratistaId()
    {
        // Ya no es necesario recargar reglas aquí
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

        return Excel::download(new DocumentosTemplateExport($this->mandante_id, $this->contratista_id, $this->regla_documental_id), 'plantilla_documentos.xlsx');
    }

    public function import()
    {
        try {
            $this->validate([
                'file' => $this->getFileValidationRule('excel_import'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->validateSecureFile($this->file, 'excel_import', 'IMPORTAR_DOCUMENTOS');
            throw $e;
        }

        $this->importing = true;
        $this->importFinished = false;
        
        // Inicializar resultados por defecto para evitar errores de "Undefined array key"
        $this->importResults = [
            'success_count' => 0,
            'failure_count' => 0,
            'failures' => [],
        ];

        // Validar si hay archivos en la carpeta de ingesta
        if ($this->totalArchivosFisicos === 0) {
            session()->flash('error', "No se encontraron archivos PDFs en la carpeta de ingesta. Por favor, suba los archivos físicos en el Paso 1 antes de continuar.");
            $this->importing = false;
            return;
        }

        try {
            $import = new DocumentosImport();
            Excel::import($import, $this->file);

            $this->importResults = [
                'success_count' => $import->successes,
                'failure_count' => count($import->failures),
                'failures' => $import->failures,
            ];
            
            if ($import->successes > 0) {
                session()->flash('message', "Se han importado {$import->successes} documentos correctamente.");
                
                // AUDITORÍA: Registrar importación masiva
                \App\Services\AuditService::log(
                    'IMPORT_DOCUMENTS',
                    "Realizó importación masiva de {$import->successes} documentos",
                    [
                        'success_count' => $import->successes,
                        'failure_count' => count($import->failures),
                    ]
                );
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado durante la importación: ' . $e->getMessage());
        }

        $this->importing = false;
        $this->importFinished = true;
        $this->reset('file');
    }

    public function resetImport()
    {
        $this->reset(['file', 'importing', 'importFinished', 'importResults']);
        $this->actualizarConteoArchivos();
    }

    /**
     * Procesa la subida de un archivo físico individual desde Dropzone
     */
    public function uploadPhysicalFile($fileData)
    {
        // El archivo viene via Livewire si se usa wire:model o similar, 
        // pero para 10k archivos usaremos un endpoint dedicado o un truco con Livewire.
        // Aquí implementaremos la lógica que llamará el JS.
    }

    public function clearTemporalFolder()
    {
        $files = Storage::disk('public')->files($this->carpetaIngesta);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
        $this->actualizarConteoArchivos();
        session()->flash('message', 'Carpeta temporal vaciada correctamente.');
    }

    public function render()
    {
        return view('livewire.importar-documentos')
            ->layout('layouts.app');
    }
}