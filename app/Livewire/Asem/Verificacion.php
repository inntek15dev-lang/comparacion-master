<?php

namespace App\Livewire\Asem;

use App\Models\Mandante;
use App\Models\RequisitoVerificacion;
use App\Models\CalendarioVerificacion;
use App\Models\ClasificacionVerificacion;
use App\Models\CatalogoAuditoriaItem;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('VERIF. CONFIG.')]
class Verificacion extends Component
{
    public $tab = 'requisitos'; // 'requisitos', 'calendario', 'consolidado', 'categorias', 'catalogo_auditoria'

    // --- CATÁLOGO AUDITORÍA ---
    public string $cat_aud_tipo    = 'observacion';
    public string $cat_aud_texto   = '';
    public bool   $cat_aud_active  = true;
    public ?int   $cat_aud_item_id = null;
    public string $cat_aud_filtro  = '';

    // Gestión de Categorías (Integrado)
    public bool $mostrarModalCategoria = false;
    public ?ClasificacionVerificacion $clasificacionActual = null;
    public string $cat_nombre = '';
    public string $cat_descripcion = '';
    public bool $cat_is_active = true;
    public string $filtroCatNombre = '';

    // Requisitos
    public $mandante_id;
    public ?RequisitoVerificacion $requisitoActual = null;
    public $nuevo_requisito_nombre;
    public $nuevo_requisito_codigo;
    public $nuevo_requisito_descripcion;
    public $nuevo_requisito_es_obligatorio = false;
    public $clasificacion_id;
    public $mandantes;

    // Calendario
    public $anio_seleccionado;
    public $meses = [];
    public $inicio_global = null; // Para mostrar siempre cuándo partió la principal

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->anio_seleccionado = date('Y');
    }

    public function updatedMandanteId()
    {
        if ($this->tab == 'calendario') {
            $this->cargarCalendario();
        }
    }

    public function cargarCalendario()
    {
        $this->meses = [];
        $this->inicio_global = null;
        
        if (!$this->mandante_id) return;

        // Buscar el inicio oficial en cualquier año
        $registroInicio = CalendarioVerificacion::where('mandante_id', $this->mandante_id)
            ->where('is_inicio', true)
            ->first();
        
        if ($registroInicio) {
            $this->inicio_global = [
                'mes' => $this->getNombreMes($registroInicio->mes),
                'anio' => $registroInicio->anio,
                'periodo' => $this->getNombrePeriodo($registroInicio->mes, $registroInicio->anio)
            ];
        }

        for ($m = 1; $m <= 12; $m++) {
            $registro = CalendarioVerificacion::where('mandante_id', $this->mandante_id)
                ->where('anio', $this->anio_seleccionado)
                ->where('mes', $m)
                ->first();
            
            $this->meses[$m] = [
                'id' => $registro->id ?? null,
                'nombre' => $this->getNombreMes($m),
                'periodo' => $this->getNombrePeriodo($m, $this->anio_seleccionado),
                'apertura' => $registro ? $registro->fecha_apertura->format('Y-m-d') : null,
                'cierre' => $registro ? $registro->fecha_cierre->format('Y-m-d') : null,
                'cierre_fuera_plazo' => $registro && $registro->fecha_cierre_fuera_plazo ? $registro->fecha_cierre_fuera_plazo->format('Y-m-d') : null,
                'emision' => $registro && $registro->fecha_emision ? $registro->fecha_emision->format('Y-m-d') : null,
                'emision_fuera_plazo' => $registro && $registro->fecha_emision_fuera_plazo ? $registro->fecha_emision_fuera_plazo->format('Y-m-d') : null,
                'is_inicio' => $registro ? $registro->is_inicio : false,
            ];
        }
    }

    public function toggleInicio($mesNum)
    {
        if (!$this->mandante_id) return;

        // 1. Quitar el "inicio" de cualquier otro mes del mismo mandante (si decides que solo haya uno)
        CalendarioVerificacion::where('mandante_id', $this->mandante_id)->update(['is_inicio' => false]);

        // 2. Marcar el mes seleccionado (si ya existe, si no avisar que debe guardarlo primero)
        $registro = CalendarioVerificacion::where('mandante_id', $this->mandante_id)
            ->where('anio', $this->anio_seleccionado)
            ->where('mes', $mesNum)
            ->first();

        if ($registro) {
            $registro->is_inicio = true;
            $registro->save();
            $this->cargarCalendario();
            session()->flash("mes_status_$mesNum", 'Marcado como Inicio.');
        } else {
            session()->flash("mes_status_$mesNum", 'Guarda el mes primero.');
        }
    }

    private function getNombreMes($mes)
    {
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $nombres[$mes];
    }

    private function getNombrePeriodo($mes, $anio)
    {
        $fecha = \Carbon\Carbon::create($anio, $mes, 1)->subMonth();
        return $this->getNombreMes($fecha->month) . ' ' . $fecha->year;
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        if ($tab == 'calendario') {
            $this->cargarCalendario();
        }
    }

    // Lógica de Requisitos
    public function editarRequisito($id)
    {
        $this->resetValidation();
        $this->requisitoActual = RequisitoVerificacion::find($id);
        $this->nuevo_requisito_nombre = $this->requisitoActual->nombre;
        $this->nuevo_requisito_codigo = $this->requisitoActual->codigo;
        $this->nuevo_requisito_descripcion = $this->requisitoActual->descripcion;
        $this->nuevo_requisito_es_obligatorio = (bool)$this->requisitoActual->es_obligatorio;
        $this->clasificacion_id = $this->requisitoActual->clasificacion_id;
    }

    public function cancelarEdicionRequisito()
    {
        $this->requisitoActual = null;
        $this->nuevo_requisito_nombre = '';
        $this->nuevo_requisito_codigo = '';
        $this->nuevo_requisito_descripcion = '';
        $this->nuevo_requisito_es_obligatorio = false;
        $this->clasificacion_id = null;
        $this->resetValidation();
    }

    public function guardarRequisito()
    {
        $this->validate([
            'mandante_id' => 'required|exists:mandantes,id',
            'nuevo_requisito_nombre' => 'required|string|max:255',
            'nuevo_requisito_codigo' => 'nullable|string|max:50',
            'nuevo_requisito_es_obligatorio' => 'boolean',
        ]);

        if ($this->requisitoActual) {
            $this->requisitoActual->update([
                'clasificacion_id' => $this->clasificacion_id ?: null,
                'codigo' => $this->nuevo_requisito_codigo,
                'nombre' => $this->nuevo_requisito_nombre,
                'descripcion' => $this->nuevo_requisito_descripcion,
                'es_obligatorio' => $this->nuevo_requisito_es_obligatorio,
            ]);
            $this->requisitoActual = null;
            session()->flash('requisito_status', 'Requisito actualizado con éxito.');
        } else {
            RequisitoVerificacion::create([
                'mandante_id' => $this->mandante_id,
                'clasificacion_id' => $this->clasificacion_id ?: null,
                'codigo' => $this->nuevo_requisito_codigo,
                'nombre' => $this->nuevo_requisito_nombre,
                'descripcion' => $this->nuevo_requisito_descripcion,
                'es_obligatorio' => $this->nuevo_requisito_es_obligatorio,
            ]);
            session()->flash('requisito_status', 'Requisito agregado con éxito.');
        }

        $this->nuevo_requisito_nombre = '';
        $this->nuevo_requisito_codigo = '';
        $this->nuevo_requisito_descripcion = '';
        $this->nuevo_requisito_es_obligatorio = false;
        $this->clasificacion_id = null;
    }

    public function eliminarRequisito($id)
    {
        RequisitoVerificacion::destroy($id);
        session()->flash('requisito_status', 'Requisito eliminado.');
    }

    // Lógica de Calendario
    public function guardarMes($mesNum)
    {
        if (!$this->mandante_id) {
            session()->flash("mes_status_$mesNum", 'Seleccione una empresa principal.');
            return;
        }

        $datos = $this->meses[$mesNum];
        
        if (!$datos['apertura'] || !$datos['cierre']) {
            session()->flash("mes_status_$mesNum", 'Debes ingresar fecha de apertura y cierre.');
            return;
        }

        $cal = CalendarioVerificacion::firstOrNew(
            ['mandante_id' => $this->mandante_id, 'anio' => $this->anio_seleccionado, 'mes' => $mesNum]
        );
        
        $cal->forceFill([
            'fecha_apertura' => $datos['apertura'], 
            'fecha_cierre' => $datos['cierre'],
            'fecha_cierre_fuera_plazo' => $datos['cierre_fuera_plazo'] ?? null,
            'fecha_emision' => $datos['emision'] ?: null,
            'fecha_emision_fuera_plazo' => $datos['emision_fuera_plazo'] ?? null,
        ]);
        
        $cal->save();
        $cal->refresh(); // Reload from DB to confirm persistence

        $this->cargarCalendario();

        session()->flash("mes_status_$mesNum", 'Guardado.');
    }

    public function eliminarMes($mesNum)
    {
        if (!$this->mandante_id) return;

        CalendarioVerificacion::where('mandante_id', $this->mandante_id)
            ->where('anio', $this->anio_seleccionado)
            ->where('mes', $mesNum)
            ->delete();

        $this->cargarCalendario();
        session()->flash("mes_status_$mesNum", 'Periodo eliminado.');
    }
    public function updatedTab()
    {
        if ($this->tab == 'catalogo_auditoria') {
            $this->cancelarCatalogoItem();
        }
    }

    // --- MÉTODOS CATÁLOGO AUDITORÍA ---
    public function guardarCatalogoItem()
    {
        $this->validate([
            'cat_aud_tipo'  => 'required|in:observacion,contingencia',
            'cat_aud_texto' => 'required|string|min:3|max:500',
        ], [
            'cat_aud_texto.required' => 'El texto es obligatorio.',
            'cat_aud_texto.min'      => 'Mínimo 3 caracteres.',
        ]);

        if ($this->cat_aud_item_id) {
            CatalogoAuditoriaItem::find($this->cat_aud_item_id)?->update([
                'tipo'      => $this->cat_aud_tipo,
                'texto'     => trim($this->cat_aud_texto),
                'is_active' => $this->cat_aud_active,
            ]);
            session()->flash('cat_aud_status', 'Ítem actualizado correctamente.');
        } else {
            CatalogoAuditoriaItem::create([
                'tipo'      => $this->cat_aud_tipo,
                'texto'     => trim($this->cat_aud_texto),
                'is_active' => $this->cat_aud_active,
            ]);
            session()->flash('cat_aud_status', 'Ítem creado correctamente.');
        }
        $this->cancelarCatalogoItem();
    }

    public function editarCatalogoItem($id)
    {
        $item = CatalogoAuditoriaItem::find($id);
        if ($item) {
            $this->cat_aud_item_id = $item->id;
            $this->cat_aud_tipo    = $item->tipo;
            $this->cat_aud_texto   = $item->texto;
            $this->cat_aud_active  = $item->is_active;
        }
    }

    public function eliminarCatalogoItem($id)
    {
        CatalogoAuditoriaItem::destroy($id);
        session()->flash('cat_aud_status', 'Ítem eliminado.');
    }

    public function toggleStatusCatalogoItem($id)
    {
        $item = CatalogoAuditoriaItem::find($id);
        if ($item) {
            $item->update(['is_active' => !$item->is_active]);
        }
    }

    public function cancelarCatalogoItem()
    {
        $this->cat_aud_item_id = null;
        $this->cat_aud_tipo    = 'observacion';
        $this->cat_aud_texto   = '';
        $this->cat_aud_active  = true;
        $this->resetValidation();
    }

    // Lógica de Gestión de Categorías
    public function abrirModalCategoria($id = null)
    {
        $this->resetValidation();
        if ($id) {
            $this->clasificacionActual = ClasificacionVerificacion::find($id);
            $this->cat_nombre = $this->clasificacionActual->nombre;
            $this->cat_descripcion = $this->clasificacionActual->descripcion ?? '';
            $this->cat_is_active = $this->clasificacionActual->is_active;
        } else {
            $this->clasificacionActual = null;
            $this->cat_nombre = '';
            $this->cat_descripcion = '';
            $this->cat_is_active = true;
        }
        $this->mostrarModalCategoria = true;
    }

    public function guardarCategoria()
    {
        $id = $this->clasificacionActual?->id ?? 'NULL';
        $validatedData = $this->validate([
            'cat_nombre' => "required|string|min:3|max:240|unique:clasificaciones_verificacion,nombre,{$id},id",
            'cat_descripcion' => 'nullable|string|max:255',
            'cat_is_active' => 'required|boolean',
        ], [
            'cat_nombre.required' => 'El nombre es obligatorio.',
            'cat_nombre.unique' => 'Esta clasificación ya existe.',
        ]);

        if ($this->clasificacionActual) {
            $this->clasificacionActual->update([
                'nombre' => $this->cat_nombre,
                'descripcion' => $this->cat_descripcion,
                'is_active' => $this->cat_is_active,
            ]);
            session()->flash('cat_status', 'Categoría actualizada.');
        } else {
            ClasificacionVerificacion::create([
                'nombre' => $this->cat_nombre,
                'descripcion' => $this->cat_descripcion,
                'is_active' => $this->cat_is_active,
            ]);
            session()->flash('cat_status', 'Categoría creada.');
        }

        $this->mostrarModalCategoria = false;
    }

    public function toggleStatusCategoria($id)
    {
        $cat = ClasificacionVerificacion::find($id);
        if ($cat) {
            $cat->update(['is_active' => !$cat->is_active]);
            session()->flash('cat_status_list', 'Estado actualizado.');
        }
    }

    public function render()
    {
        $requisitos = [];
        if ($this->mandante_id) {
            $requisitos = RequisitoVerificacion::with('clasificacion')
                ->where('mandante_id', $this->mandante_id)
                ->orderBy('nombre')
                ->get();
        }

        $clasificaciones = ClasificacionVerificacion::where('is_active', true)->orderBy('nombre')->get();
        
        // Listado para la pestaña de gestión
        $queryCat = ClasificacionVerificacion::query();
        if (!empty($this->filtroCatNombre)) {
            $queryCat->where('nombre', 'like', '%' . $this->filtroCatNombre . '%');
        }
        $categoriasGestion = $queryCat->orderBy('nombre')->get();

        $consolidado = [];
        if ($this->tab == 'consolidado') {
            $consolidado = CalendarioVerificacion::with('mandante')
                ->where('anio', $this->anio_seleccionado)
                ->orderBy('mandante_id')
                ->orderBy('mes')
                ->get();
        }

        // Catálogo Auditoría (solo si la tabla existe / pestaña activa)
        $catalogoItems     = collect();
        $catalogoObsCount  = 0;
        $catalogoContCount = 0;

        try {
            $queryCatAud = CatalogoAuditoriaItem::query();
            if ($this->cat_aud_filtro) {
                $queryCatAud->where('tipo', $this->cat_aud_filtro);
            }
            if ($this->tab === 'catalogo_auditoria') {
                $catalogoItems = $queryCatAud->orderBy('tipo')->orderBy('texto')->get();
            }
            $catalogoObsCount  = CatalogoAuditoriaItem::where('tipo', 'observacion')->count();
            $catalogoContCount = CatalogoAuditoriaItem::where('tipo', 'contingencia')->count();
        } catch (\Exception $e) {
            // Tabla aún no migrada — silenciado
        }

        return view('livewire.asem.verificacion', [
            'requisitos'        => $requisitos,
            'consolidado'       => $consolidado,
            'clasificaciones'   => $clasificaciones,
            'categoriasGestion' => $categoriasGestion,
            'catalogoItems'     => $catalogoItems,
            'catalogoObsCount'  => $catalogoObsCount,
            'catalogoContCount' => $catalogoContCount,
        ])->layout('layouts.app');
    }
}
