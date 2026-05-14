<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth; // Para verificar el rol del usuario

#[Layout('layouts.app')] // Usamos el layout principal de Breeze
#[Title('Gestión de Listados Universales')] // Título de la página
class GestionListadosUniversalesHub extends Component
{
    public array $listados = [];

    public function mount()
    {
        // Protección adicional a nivel de componente:
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'Acceso no autorizado a esta sección.');
            return redirect()->route('dashboard');
        }

        $this->listados = [
            ['titulo' => 'Aclaraciones de Criterio', 'ruta' => 'gestion.aclaraciones-criterio', 'desc' => 'Textos de ayuda para criterios.'],
            ['titulo' => 'Cargos por Principal', 'ruta' => 'gestion.cargos-mandante', 'desc' => 'Gestionar cargos por Principal (ej. Mecánico, Supervisor).'],
            ['titulo' => 'Colores de Vehículo', 'ruta' => 'gestion.colores-vehiculo', 'desc' => 'Gestionar colores de vehículos (ej. Rojo, Azul).'],
            ['titulo' => 'Comunas', 'ruta' => 'gestion.comunas', 'desc' => 'Comunas por región.'],
            ['titulo' => 'Condiciones Fecha Ingreso', 'ruta' => 'gestion.condiciones-fecha-ingreso', 'desc' => 'Condiciones por fecha de ingreso.'],
            ['titulo' => 'Condiciones Personales', 'ruta' => 'gestion.tipos-condicion-personal', 'desc' => 'Condiciones para trabajadores.'],
            ['titulo' => 'Criterios de Evaluación', 'ruta' => 'gestion.criterios-evaluacion', 'desc' => 'Criterios para validación.'],
            ['titulo' => 'Documentos', 'ruta' => 'gestion.documentos', 'desc' => 'Tipos de documentos genéricos.'],
            ['titulo' => 'Estados Civiles', 'ruta' => 'gestion.estados-civiles', 'desc' => 'Gestionar estados civiles.'],
            ['titulo' => 'Etnias / Pueblos Originarios', 'ruta' => 'gestion.etnias', 'desc' => 'Gestionar etnias y pueblos originarios.'],
            ['titulo' => 'Formatos de Muestra', 'ruta' => 'gestion.formatos-muestra', 'desc' => 'Archivos PDF de muestra.'],
            ['titulo' => 'Marcas de Vehículo', 'ruta' => 'gestion.marcas-vehiculo', 'desc' => 'Gestionar marcas de vehículos (ej. Toyota, Ford).'],
            ['titulo' => 'Mutualidades', 'ruta' => 'gestion.mutualidades', 'desc' => 'Mutualidades de seguridad.'],
            ['titulo' => 'Nacionalidades', 'ruta' => 'gestion.nacionalidades', 'desc' => 'Nacionalidades de trabajadores.'],
            ['titulo' => 'Niveles Educacionales', 'ruta' => 'gestion.niveles-educacionales', 'desc' => 'Gestionar niveles de educación.'],
            ['titulo' => 'Observaciones de Documento', 'ruta' => 'gestion.observaciones-documento', 'desc' => 'Plantillas para observaciones.'],
            ['titulo' => 'Rangos Cant. Trabajadores', 'ruta' => 'gestion.rangos-cantidad-trabajadores', 'desc' => 'Rangos para cantidad de trabajadores.'],
            ['titulo' => 'Regiones', 'ruta' => 'gestion.regiones', 'desc' => 'Regiones geográficas.'],
            ['titulo' => 'Rubros', 'ruta' => 'gestion.rubros', 'desc' => 'Rubros de empresas.'],
            ['titulo' => 'Sexos', 'ruta' => 'gestion.sexos', 'desc' => 'Sexos para perfiles.'],
            ['titulo' => 'Sub-Criterios', 'ruta' => 'gestion.sub-criterios', 'desc' => 'Sub-criterios para evaluación.'],
            ['titulo' => 'Sub-Tipos de Vehículo', 'ruta' => 'gestion.sub-tipos-vehiculo-mandante', 'desc' => 'Gestionar sub-tipos de vehículos por Principal.'],
            ['titulo' => 'Tenencias de Vehículo', 'ruta' => 'gestion.tenencias-vehiculo', 'desc' => 'Gestionar tipo de tenencia (ej. Propio, Leasing).'],
            ['titulo' => 'Textos de Rechazo', 'ruta' => 'gestion.textos-rechazo', 'desc' => 'Plantillas para rechazos.'],
            ['titulo' => 'Tipos de Carga', 'ruta' => 'gestion.tipos-carga', 'desc' => 'Tipos de carga documental.'],
            ['titulo' => 'Tipos Condición (Empresa)', 'ruta' => 'gestion.tipos-condicion', 'desc' => 'Condiciones para empresas.'],
            ['titulo' => 'Tipos de Condición (Vehículos)', 'ruta' => 'gestion.tipos-condicion-vehiculo', 'desc' => 'Condiciones para vehículos y equipos.'],
            ['titulo' => 'Tipos de Contrato', 'ruta' => 'gestion.tipos-contrato', 'desc' => 'Tipos de vinculación (Permanente, Esporádico).'],
            ['titulo' => 'Tipos de Embarcación', 'ruta' => 'gestion.tipos-embarcacion', 'desc' => 'Gestionar tipos de embarcación.'],
            ['titulo' => 'Tipos de Empresa Legal', 'ruta' => 'gestion.tipos-empresa-legal', 'desc' => 'Tipos de Empresa Legal.'],
            ['titulo' => 'Tipos de Entidad', 'ruta' => 'gestion.tipos-entidad-controlable', 'desc' => 'Entidades controlables (Persona, Vehículo, etc.).'],
            ['titulo' => 'Tipos de Maquinaria', 'ruta' => 'gestion.tipos-maquinaria', 'desc' => 'Gestionar tipos de maquinaria.'],
            ['titulo' => 'Tipos de Permanencia', 'ruta' => 'gestion.tipos-permanencia', 'desc' => 'Temporales, definitivas, etc.'],
            ['titulo' => 'Tipos de Vehículo', 'ruta' => 'gestion.tipos-vehiculo', 'desc' => 'Gestionar categorías de vehículos.'],
            ['titulo' => 'Unidades Operativas', 'ruta' => 'gestion.unidades-organizacionales-mandante', 'desc' => 'Gestionar UOs por Principal (jerarquía).'],
            ['titulo' => 'Principales', 'ruta' => 'gestion.mandantes', 'desc' => 'Gestión de Empresas Principales (Mandantes).'],
        ];

        // Ordenar alfabéticamente por título
        usort($this->listados, function ($a, $b) {
            return strcasecmp($a['titulo'], $b['titulo']);
        });
    }

    public function render()
    {
        return view('livewire.gestion-listados-universales-hub');
    }
}