<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Popup;
use App\Models\PopupVisualizacion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Gestión de Popups')]
class GestionPopups extends Component
{
    use WithPagination;
    use WithFileUploads, \App\Traits\ValidatesFileUpload;

    public bool $mostrarModal = false;
    public bool $mostrarModalRegistro = false;
    public ?Popup $popupActual = null;
    public ?Popup $popupRegistro = null;

    // Campos del formulario
    public string $titulo = '';
    public string $contenido = '';
    public $archivoContenido = null;
    public ?string $archivoContenidoExistente = null;
    public array $roles_destino = [];
    public int $max_visualizaciones = 1;
    public bool $requiere_aceptacion = false;
    public string $texto_aceptacion = '';
    public string $tipo_interaccion = 'solo_cerrar';
    public ?string $url_destino = null;
    public string $fecha_inicio = '';
    public ?string $fecha_fin = null;
    public bool $is_active = true;
    public ?int $mandante_id = null;

    // Filtros
    public string $filtroTitulo = '';
    public string $filtroEstado = 'todos';
    public string $filtroVigencia = 'todos';
    public string $filtroMandante = 'todos';

    // Roles y Mandantes disponibles
    public array $rolesDisponibles = [];
    public array $mandantesDisponibles = [];

    protected function rules()
    {
        return [
            'titulo' => 'required|string|min:3|max:150',
            'contenido' => 'required_without:archivoContenido|string|max:10000',
            'archivoContenido' => 'nullable|' . $this->getFileValidationRule('popup'),
            'roles_destino' => 'required|array|min:1',
            'max_visualizaciones' => 'required|integer|min:0|max:100',
            'requiere_aceptacion' => 'boolean',
            'texto_aceptacion' => 'required_if:requiere_aceptacion,true|nullable|string|max:500',
            'tipo_interaccion' => 'required|in:solo_cerrar,requiere_click',
            'url_destino' => 'nullable|url|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'is_active' => 'boolean',
            'mandante_id' => 'nullable|exists:mandantes,id',
        ];
    }

    protected $messages = [
        'titulo.required' => 'El título del popup es obligatorio.',
        'titulo.min' => 'El título debe tener al menos 3 caracteres.',
        'contenido.required_without' => 'Debe ingresar contenido o subir un archivo.',
        'roles_destino.required' => 'Debe seleccionar al menos un rol.',
        'roles_destino.min' => 'Debe seleccionar al menos un rol.',
        'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        'texto_aceptacion.required_if' => 'El texto de aceptación es obligatorio cuando se requiere aceptación.',
        'archivoContenido.mimes' => 'El archivo debe ser de tipo .txt o .html.',
        'archivoContenido.max' => 'El archivo no debe superar 1MB.',
    ];

    public function updatedFiltroTitulo() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }
    public function updatedFiltroVigencia() { $this->resetPage(); }
    public function updatedFiltroMandante() { $this->resetPage(); }

    public function mount()
    {
        $this->popupActual = new Popup();
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->rolesDisponibles = Role::pluck('name', 'name')->toArray();
        $this->mandantesDisponibles = \App\Models\Mandante::where('is_active', true)->orderBy('razon_social')->pluck('razon_social', 'id')->toArray();
    }

    public function render()
    {
        $query = Popup::with(['creador', 'mandante']);

        if (!empty($this->filtroTitulo)) {
            $query->where('titulo', 'like', '%' . $this->filtroTitulo . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        if ($this->filtroVigencia === 'vigentes') {
            $query->vigentes();
        } elseif ($this->filtroVigencia === 'expirados') {
            $hoy = now()->toDateString();
            $query->where('fecha_fin', '<', $hoy);
        } elseif ($this->filtroVigencia === 'programados') {
            $hoy = now()->toDateString();
            $query->where('fecha_inicio', '>', $hoy);
        }

        if ($this->filtroMandante === 'global') {
            $query->whereNull('mandante_id');
        } elseif ($this->filtroMandante !== 'todos') {
            $query->where('mandante_id', $this->filtroMandante);
        }

        $popups = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.gestion-popups', [
            'popups' => $popups,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin'])) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->resetFormulario();
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Popup $popup)
    {
        if (!Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin'])) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->popupActual = $popup;
        $this->titulo = $popup->titulo;
        $this->contenido = $popup->contenido ?? '';
        $this->archivoContenidoExistente = $popup->archivo_contenido;
        $this->roles_destino = $popup->roles_destino ?? [];
        $this->max_visualizaciones = $popup->max_visualizaciones;
        $this->requiere_aceptacion = $popup->requiere_aceptacion;
        $this->texto_aceptacion = $popup->texto_aceptacion ?? '';
        $this->tipo_interaccion = $popup->tipo_interaccion;
        $this->url_destino = $popup->url_destino;
        $this->fecha_inicio = $popup->fecha_inicio->format('Y-m-d');
        $this->fecha_fin = $popup->fecha_fin ? $popup->fecha_fin->format('Y-m-d') : null;
        $this->is_active = $popup->is_active;
        $this->mandante_id = $popup->mandante_id;
        $this->mostrarModal = true;
    }

    public function guardarPopup()
    {
        if (!Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin'])) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->archivoContenido) {
                $this->validateSecureFile($this->archivoContenido, 'popup', 'GESTION_POPUPS');
            }
            throw $e;
        }

        $archivoPath = $this->archivoContenidoExistente;

        // Subir nuevo archivo si se proporciona
        if ($this->archivoContenido) {
            // Eliminar archivo anterior si existe
            if ($archivoPath && Storage::disk('public')->exists($archivoPath)) {
                Storage::disk('public')->delete($archivoPath);
            }
            $archivoPath = $this->archivoContenido->store('popups', 'public');
        }

        $data = [
            'titulo' => $this->titulo,
            'contenido' => $this->contenido,
            'archivo_contenido' => $archivoPath,
            'roles_destino' => $this->roles_destino,
            'max_visualizaciones' => $this->max_visualizaciones,
            'requiere_aceptacion' => $this->requiere_aceptacion,
            'texto_aceptacion' => $this->requiere_aceptacion ? $this->texto_aceptacion : null,
            'tipo_interaccion' => $this->tipo_interaccion,
            'url_destino' => $this->url_destino ?: null,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin ?: null,
            'is_active' => $this->is_active,
            'mandante_id' => $this->mandante_id ?: null,
        ];

        if (empty($this->popupActual->id)) {
            $data['created_by'] = Auth::id();
            Popup::create($data);
            session()->flash('success', 'Popup creado exitosamente.');
        } else {
            $this->popupActual->update($data);
            session()->flash('success', 'Popup actualizado exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->resetFormulario();
    }

    private function resetFormulario()
    {
        $this->popupActual = new Popup();
        $this->titulo = '';
        $this->contenido = '';
        $this->archivoContenido = null;
        $this->archivoContenidoExistente = null;
        $this->roles_destino = [];
        $this->max_visualizaciones = 1;
        $this->requiere_aceptacion = false;
        $this->texto_aceptacion = '';
        $this->tipo_interaccion = 'solo_cerrar';
        $this->url_destino = null;
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->fecha_fin = null;
        $this->is_active = true;
        $this->mandante_id = null;
    }

    public function confirmarAlternarEstado(Popup $popup)
    {
        if (!Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin'])) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$popup->is_active;
        $popup->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del popup actualizado exitosamente.');
    }

    public function eliminarPopup(Popup $popup)
    {
        if (!Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin'])) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }

        // Eliminar archivo si existe
        if ($popup->archivo_contenido && Storage::disk('public')->exists($popup->archivo_contenido)) {
            Storage::disk('public')->delete($popup->archivo_contenido);
        }

        $popup->delete();
        session()->flash('success', 'Popup eliminado exitosamente.');
    }

    public function eliminarArchivoExistente()
    {
        if ($this->archivoContenidoExistente && Storage::disk('public')->exists($this->archivoContenidoExistente)) {
            Storage::disk('public')->delete($this->archivoContenidoExistente);
        }
        $this->archivoContenidoExistente = null;
        
        if ($this->popupActual && $this->popupActual->id) {
            $this->popupActual->update(['archivo_contenido' => null]);
        }
    }

    public function verRegistroAceptaciones(Popup $popup)
    {
        $this->popupRegistro = $popup;
        $this->mostrarModalRegistro = true;
    }

    public function cerrarModalRegistro()
    {
        $this->mostrarModalRegistro = false;
        $this->popupRegistro = null;
    }

    public function getAceptacionesProperty()
    {
        if (!$this->popupRegistro) {
            return collect();
        }

        return PopupVisualizacion::with(['user.contratista'])
            ->where('popup_id', $this->popupRegistro->id)
            ->where('acepto_condiciones', true)
            ->orderBy('ultima_visualizacion', 'desc')
            ->get();
    }

    public function exportarAceptacionesExcel()
    {
        if (!$this->popupRegistro) {
            return;
        }

        $aceptaciones = PopupVisualizacion::with(['user.contratista'])
            ->where('popup_id', $this->popupRegistro->id)
            ->where('acepto_condiciones', true)
            ->orderBy('ultima_visualizacion', 'desc')
            ->get();

        $filename = 'aceptaciones_popup_' . $this->popupRegistro->id . '_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($aceptaciones) {
            $file = fopen('php://output', 'w');
            
            // BOM para Excel reconozca UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeceras
            fputcsv($file, [
                'Popup',
                'Usuario',
                'Email',
                'Contratista',
                'RUT Contratista',
                'Fecha Aceptación',
                'Hora Aceptación'
            ], ';');

            // Datos
            foreach ($aceptaciones as $aceptacion) {
                fputcsv($file, [
                    $this->popupRegistro->titulo,
                    $aceptacion->user->name ?? 'Usuario eliminado',
                    $aceptacion->user->email ?? '-',
                    $aceptacion->user->contratista->razon_social ?? 'Sin contratista',
                    $aceptacion->user->contratista->rut ?? '-',
                    $aceptacion->ultima_visualizacion->format('d/m/Y'),
                    $aceptacion->ultima_visualizacion->format('H:i:s'),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
