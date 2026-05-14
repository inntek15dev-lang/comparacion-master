<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// --- CONTROLADOR PARA LAS PÁGINAS PÚBLICAS ---
use App\Http\Controllers\PageController;

// --- Importaciones de Componentes para ASEM_Admin ---
use App\Livewire\GestionListadosUniversalesHub;
use App\Livewire\ListarNombreDocumentos;
use App\Livewire\GestionRubros;
use App\Livewire\GestionTiposEmpresaLegal;
use App\Livewire\GestionTiposCondicion;
use App\Livewire\GestionRangosCantidadTrabajadores;
use App\Livewire\GestionMutualidades;
use App\Livewire\GestionNacionalidades;
use App\Livewire\GestionTiposCondicionPersonal;
use App\Livewire\GestionTiposCondicionVehiculo;
use App\Livewire\GestionSexos;
use App\Livewire\GestionEstadosCiviles;
use App\Livewire\GestionEtnias;
use App\Livewire\GestionNivelesEducacionales;
use App\Livewire\GestionCriteriosEvaluacion;
use App\Livewire\GestionSubCriterios;
use App\Livewire\GestionTextosRechazo;
use App\Livewire\GestionAclaracionesCriterio;
use App\Livewire\GestionObservacionesDocumento;
use App\Livewire\GestionCondicionesFechaIngreso;
use App\Livewire\GestionTiposCarga;
use App\Livewire\GestionTiposVencimiento;
use App\Livewire\GestionFormatosMuestra;
use App\Livewire\GestionTiposEntidadControlable;
use App\Livewire\GestionTiposVehiculo;
use App\Livewire\GestionMarcasVehiculo;
use App\Livewire\GestionColoresVehiculo;
use App\Livewire\GestionTenenciasVehiculo;
use App\Livewire\GestionTiposMaquinaria;
use App\Livewire\GestionTiposEmbarcacion;
use App\Livewire\GestionRegiones;
use App\Livewire\GestionComunas;
use App\Livewire\GestionMandantes;
use App\Livewire\GestionUnidadesOrganizacionalesMandante;
use App\Livewire\GestionCargosMandante;
use App\Livewire\GestionSubTiposVehiculoMandante;
use App\Livewire\GestionContratistas;
use App\Livewire\GestionReglasDocumentales;
use App\Livewire\GestionUsuarios;
use App\Livewire\GestionDependencias;
// Componentes de ASEM
use App\Livewire\Asem\AsignacionDocumentos;
use App\Livewire\Asem\PanelValidacion;
use App\Livewire\Asem\RevisarDocumento;
use App\Livewire\Asem\GestionGeneralDocumentos;
use App\Livewire\Asem\GestionCriticidadGeneral;
use App\Livewire\Asem\GestionCriticidadExcepciones;
use App\Livewire\Asem\GestionSolicitudesVinculacion;
use App\Livewire\Asem\FacturacionMensual;
use App\Livewire\Asem\SupervisionGlobal;
use App\Livewire\Asem\SupervisionDetalleGlobal;
use App\Livewire\Asem\OperacionesGlobales;
use App\Livewire\Asem\GestionAsignacionAutomatica;
use App\Livewire\Asem\Informes\PanelInformes;
use App\Livewire\ImportarContratistas;
use App\Livewire\ImportarTrabajadores;
use App\Livewire\ImportarVehiculos;
use App\Livewire\ImportarDocumentos;
use App\Livewire\SincronizarDocumentos;
use App\Livewire\ImportarVerificacionesHistoricas;
use App\Livewire\ImportarDotacionAnterior;
use App\Livewire\Asem\GestionExcepciones;
use App\Livewire\GestionPopups;
use App\Livewire\GestionTiposContrato;
use App\Livewire\Asem\Verificacion as AsemVerificacion;
use App\Livewire\GestionClasificacionesVerificacion;
use App\Livewire\GestionTiposPermanencia;
use App\Livewire\Asem\RevisionIaAcreditacion;  // ← IA Acreditación

// --- Importaciones de Componentes para Contratista_Admin ---
use App\Livewire\Contratista\PanelOperacion;
use App\Livewire\Contratista\MiFichaPage;
use App\Livewire\Contratista\SolicitudesSubcontratistasPage;
use App\Livewire\Contratista\ReporteDotacion;
use App\Livewire\Contratista\Verificacion as ContratistaVerificacion;
use App\Livewire\Contratista\VerificacionLegacyCarga;

// --- Importaciones de Componentes para Mandante ---
use App\Livewire\Mandante\PanelValidacionMandante;
use App\Livewire\Mandante\GestionGeneralDocumentos as MandanteGestionGeneralDocumentos;
use App\Livewire\Mandante\GestionContratistas as MandanteGestionContratistas;
use App\Livewire\Mandante\Operaciones as MandanteOperaciones;
use App\Livewire\Mandante\Supervision as MandanteSupervision;
use App\Livewire\Mandante\SupervisionDetalle as MandanteSupervisionDetalle;
use App\Livewire\Mandante\GestionExcepciones as MandanteGestionExcepciones;
use App\Livewire\Mandante\Verificacion as MandanteVerificacion;
use App\Livewire\Mandante\InformarContratistas;
use App\Livewire\Mandante\DashboardEjecutivo as MandanteDashboardEjecutivo;

// --- Importación de Componente para Registro Público ---
use App\Livewire\Publico\FormularioRegistroContratista;

// --- Importación de Componente para 2FA ---
use App\Livewire\Pages\Auth\TwoFactorChallenge;


Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/quienes-somos', [PageController::class, 'about'])->name('about');

Route::get('/registro-contratista', FormularioRegistroContratista::class)->name('public.registro');

Route::get('/mostrar-archivo/{filePath}', function (Illuminate\Http\Request $request, string $filePath) {
    if (!Storage::disk('public')->exists($filePath)) {
        abort(404, 'El archivo solicitado no fue encontrado.');
    }
    
    $name = $request->query('name') ?: basename($filePath);

    if ($request->query('download')) {
        return Storage::disk('public')->download($filePath, $name);
    }

    return Storage::disk('public')->response($filePath);
})->where('filePath', '.*')->name('archivo.publico')->middleware('auth');

// ==================================================================================
// RUTAS DE DESCARGA SEGURA — ESCUDO CRIPTOGRÁFICO
// Todos los documentos pasan por aquí. El controller verifica auth + permisos.
Route::middleware('auth')->group(function () {
    Route::get('/documento-seguro/{id}', [\App\Http\Controllers\DocumentoSeguroController::class, 'descargar'])
        ->name('documento.seguro.descargar')
        ->where('id', '[0-9]+');

    Route::get('/documento-verificacion/{id}', [\App\Http\Controllers\DocumentoSeguroController::class, 'descargarComplementario'])
        ->name('documento.verificacion.descargar')
        ->where('id', '[0-9]+');

    Route::get('/documento-verificacion-legacy/{id}', [\App\Http\Controllers\DocumentoSeguroController::class, 'descargarVerificacion'])
        ->name('documento.verificacion_legacy.descargar')
        ->where('id', '[0-9]+');
});

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

Route::get('/gestion/contratistas/{contratistaId}/ver', GestionContratistas::class)
    ->name('gestion.contratistas.ver')
    ->middleware(['auth', 'role:ASEM_Admin|Mandante_Admin']);


Route::prefix('gestion')->middleware(['auth', 'role:ASEM_Admin|OVAL_Admin'])->name('gestion.')->group(function () {
    Route::get('/listados-universales', GestionListadosUniversalesHub::class)->name('listados.hub');
    Route::get('/documentos', ListarNombreDocumentos::class)->name('documentos');
    Route::get('/rubros', GestionRubros::class)->name('rubros');
    Route::get('/tipos-empresa-legal', GestionTiposEmpresaLegal::class)->name('tipos-empresa-legal');
    Route::get('/tipos-condicion', GestionTiposCondicion::class)->name('tipos-condicion');
    Route::get('/rangos-cantidad-trabajadores', GestionRangosCantidadTrabajadores::class)->name('rangos-cantidad-trabajadores');
    Route::get('/mutualidades', GestionMutualidades::class)->name('mutualidades');
    Route::get('/nacionalidades', GestionNacionalidades::class)->name('nacionalidades');
    Route::get('/tipos-condicion-personal', GestionTiposCondicionPersonal::class)->name('tipos-condicion-personal');
    Route::get('/tipos-condicion-vehiculo', GestionTiposCondicionVehiculo::class)->name('tipos-condicion-vehiculo');
    Route::get('/sexos', GestionSexos::class)->name('sexos');
    Route::get('/tipos-permanencia', GestionTiposPermanencia::class)->name('tipos-permanencia');
    Route::get('/estados-civiles', GestionEstadosCiviles::class)->name('estados-civiles');
    Route::get('/etnias', GestionEtnias::class)->name('etnias');
    Route::get('/niveles-educacionales', GestionNivelesEducacionales::class)->name('niveles-educacionales');
    Route::get('/criterios-evaluacion', GestionCriteriosEvaluacion::class)->name('criterios-evaluacion');
    Route::get('/sub-criterios', GestionSubCriterios::class)->name('sub-criterios');
    Route::get('/textos-rechazo', GestionTextosRechazo::class)->name('textos-rechazo');
    Route::get('/aclaraciones-criterio', GestionAclaracionesCriterio::class)->name('aclaraciones-criterio');
    Route::get('/observaciones-documento', GestionObservacionesDocumento::class)->name('observaciones-documento');
    Route::get('/condiciones-fecha-ingreso', GestionCondicionesFechaIngreso::class)->name('condiciones-fecha-ingreso');
    Route::get('/tipos-carga', GestionTiposCarga::class)->name('tipos-carga');
    Route::get('/tipos-vencimiento', GestionTiposVencimiento::class)->name('tipos-vencimiento');
    Route::get('/tipos-contrato', GestionTiposContrato::class)->name('tipos-contrato');
    Route::get('/clasificaciones-verificacion', GestionClasificacionesVerificacion::class)->name('clasificaciones-verificacion');
    Route::get('/formatos-muestra', GestionFormatosMuestra::class)->name('formatos-muestra');
    Route::get('/tipos-entidad-controlable', GestionTiposEntidadControlable::class)->name('tipos-entidad-controlable');
    Route::get('/tipos-vehiculo', GestionTiposVehiculo::class)->name('tipos-vehiculo');
    Route::get('/marcas-vehiculo', GestionMarcasVehiculo::class)->name('marcas-vehiculo');
    Route::get('/colores-vehiculo', GestionColoresVehiculo::class)->name('colores-vehiculo');
    Route::get('/tenencias-vehiculo', GestionTenenciasVehiculo::class)->name('tenencias-vehiculo');
    Route::get('/tipos-maquinaria', GestionTiposMaquinaria::class)->name('tipos-maquinaria');
    Route::get('/tipos-embarcacion', GestionTiposEmbarcacion::class)->name('tipos-embarcacion');
    Route::get('/regiones', GestionRegiones::class)->name('regiones');
    Route::get('/comunas', GestionComunas::class)->name('comunas');
    Route::get('/mandantes', GestionMandantes::class)->name('mandantes');
    Route::get('/unidades-organizacionales-mandante', GestionUnidadesOrganizacionalesMandante::class)->name('unidades-organizacionales-mandante');
    Route::get('/dependencias', GestionDependencias::class)->name('dependencias');
    Route::get('/cargos-mandante', GestionCargosMandante::class)->name('cargos-mandante');
    Route::get('/sub-tipos-vehiculo-mandante', GestionSubTiposVehiculoMandante::class)->name('sub-tipos-vehiculo-mandante');
    Route::get('/contratistas', GestionContratistas::class)->name('contratistas');
    Route::get('/solicitudes-vinculacion/crear-manual', FormularioRegistroContratista::class)->name('solicitudes.crear-manual');
    Route::get('/usuarios', GestionUsuarios::class)->name('usuarios');
    Route::get('/reglas-documentales', GestionReglasDocumentales::class)->name('reglas-documentales');
    Route::get('/registro-actividad', \App\Livewire\GestionRegistroActividad::class)->name('registro-actividad');
    Route::get('/criticidad-general', GestionCriticidadGeneral::class)->name('criticidad.general');
    Route::get('/criticidad-excepciones', GestionCriticidadExcepciones::class)->name('criticidad.excepciones');
    Route::get('/asignacion-documentos', AsignacionDocumentos::class)->name('asignacion-documentos');
    Route::get('/gestion-general', GestionGeneralDocumentos::class)->name('gestion-general');
    Route::get('/solicitudes-vinculacion', GestionSolicitudesVinculacion::class)->name('solicitudes-vinculacion');
    Route::get('/facturacion-mensual', FacturacionMensual::class)->name('facturacion-mensual');
    Route::get('/supervision-global', SupervisionGlobal::class)->name('supervision-global');
    Route::get('/supervision-detalle/{contratistaId}/{mandanteId}/{lugarDeTrabajoId}/{uoId}', SupervisionDetalleGlobal::class)->name('supervision-detalle');
    Route::get('/operaciones-globales', OperacionesGlobales::class)->name('operaciones-globales');
    Route::get('/asignacion-automatica', GestionAsignacionAutomatica::class)->name('asignacion-automatica');
    Route::get('/informes', PanelInformes::class)->name('informes');
    
    Route::get('/importar/contratistas', ImportarContratistas::class)->name('importar.contratistas');
    Route::get('/importar/trabajadores', ImportarTrabajadores::class)->name('importar.trabajadores');
    Route::get('/importar/vehiculos', ImportarVehiculos::class)->name('importar.vehiculos');
    Route::get('/importar/documentos', ImportarDocumentos::class)->name('importar.documentos');
    Route::get('/importar/verificaciones-historicas', ImportarVerificacionesHistoricas::class)->name('importar.verificaciones-historicas');
    Route::get('/importar/dotacion-anterior', ImportarDotacionAnterior::class)->name('importar.dotacion-anterior');
    Route::post('/importar/documentos/fisicos', [\App\Http\Controllers\DocumentosFisicosController::class, 'store'])->name('importar.documentos.fisicos');

    // ─── MÓDULO SINCRONIZACIÓN DESDE SISTEMA OBSOLETO ───
    Route::get('/sincronizar/documentos', SincronizarDocumentos::class)->name('sincronizar.documentos');
    Route::post('/sincronizar/documentos/fisicos', [\App\Http\Controllers\SincronizacionFisicosController::class, 'store'])->name('sincronizar.documentos.fisicos');

    Route::get('/gestion-excepciones', GestionExcepciones::class)->name('excepciones');
    Route::get('/popups', GestionPopups::class)->name('popups');
    Route::get('/verificacion', AsemVerificacion::class)->name('verificacion');

    // ─── MÓDULO IA ACREDITACIÓN ──────────────────────────────────────────────
    Route::get('/ia-acreditacion', RevisionIaAcreditacion::class)->name('ia-acreditacion');
});

Route::get('/revisar-documento/{documentoId}', RevisarDocumento::class)
    ->middleware(['auth'])
    ->name('document.revisar');

Route::prefix('asem')->middleware(['auth', 'role:ASEM_Admin|ASEM_Validator'])->name('asem.')->group(function () {
    Route::get('/panel-validacion', PanelValidacion::class)->name('panel-validacion');
});

Route::prefix('contratista')->middleware(['auth', 'role:Contratista_Admin|Contratista_User|Subcontratista'])->name('contratista.')->group(function () {
    Route::get('/', PanelOperacion::class)->name('panel-operacion');
    Route::get('/mi-ficha-empresa', MiFichaPage::class)->name('mi-ficha');
    Route::get('/solicitudes-subcontratistas', SolicitudesSubcontratistasPage::class)->name('solicitudes-sub');
    Route::get('/reporte-dotacion', ReporteDotacion::class)->name('reporte-dotacion');
    Route::get('/verificacion', ContratistaVerificacion::class)->name('verificacion');
    Route::get('/verificacion-legacy', \App\Livewire\Contratista\VerificacionLegacy::class)->name('verificacion-legacy');
    Route::get('/verificacion-legacy-carga', VerificacionLegacyCarga::class)->name('verificacion-legacy-carga');
    // Gestión de usuarios solo para Contratista_Admin
    Route::get('/gestion-usuarios', GestionUsuarios::class)->name('gestion-usuarios')->middleware('role:Contratista_Admin');
    Route::get('/mis-vinculaciones', \App\Livewire\Contratista\MisVinculaciones::class)->name('mis-vinculaciones');
    Route::get('/mis-subcontratistas', \App\Livewire\Contratista\MisSubcontratistas::class)->name('mis-subcontratistas')->middleware('role:Contratista_Admin');
    Route::get('/solicitar-sub-contratista', \App\Livewire\Contratista\SolicitudSubContratista::class)->name('solicitar-sub-contratista')->middleware('role:Contratista_Admin');
});

Route::prefix('mandante')->middleware(['auth', 'role:Mandante_Admin|Mandante_Validator|Mandante_Ver|ASEM_Admin'])->name('mandante.')->group(function () {
    Route::get('/dashboard-ejecutivo', MandanteDashboardEjecutivo::class)->name('dashboard-ejecutivo')->middleware('role:Mandante_Admin');
    Route::get('/panel-validacion', PanelValidacionMandante::class)->name('panel-validacion');
    
    Route::get('/gestion-general-documentos', MandanteGestionGeneralDocumentos::class)->name('gestion-general-documentos')->middleware('role:Mandante_Admin|Mandante_Ver');
    Route::get('/gestion-contratistas', MandanteGestionContratistas::class)->name('gestion-contratistas')->middleware('role:Mandante_Admin|Mandante_Ver');
    Route::get('/gestion-entidades', MandanteOperaciones::class)->name('gestion-entidades')->middleware('role:Mandante_Admin|Mandante_Ver');
    
    Route::get('/supervision', MandanteSupervision::class)->name('supervision')->middleware('role:Mandante_Admin|Mandante_Ver');
    Route::get('/supervision-detalle/{contratistaId}/{mandanteId}/{lugarDeTrabajoId}/{uoId}', MandanteSupervisionDetalle::class)->name('supervision-detalle')->middleware('role:Mandante_Admin|Mandante_Ver');
    
    Route::get('/solicitudes-vinculacion', GestionSolicitudesVinculacion::class)->name('solicitudes-vinculacion')->middleware('role:Mandante_Admin|Mandante_Ver');

    // ================== INICIO DE LA MODIFICACIÓN (NUEVA RUTA MANDANTE) ==================
    Route::get('/gestion-excepciones', MandanteGestionExcepciones::class)->name('excepciones')->middleware('role:Mandante_Admin|Mandante_Ver');
    // ================== FIN DE LA MODIFICACIÓN (NUEVA RUTA MANDANTE) ====================
    Route::get('/verificacion', MandanteVerificacion::class)->name('verificacion')->middleware('role:Mandante_Admin|Mandante_Ver');

    Route::get('/informar-contratistas', InformarContratistas::class)->name('informar-contratistas')->middleware('role:Mandante_Admin|Mandante_Ver|ASEM_Admin');
    Route::get('/gestion-usuarios', GestionUsuarios::class)->name('gestion-usuarios')->middleware('role:Mandante_Admin');
});

// ================== RUTAS PARA ROLES DE VERIFICACIÓN (Módulo 9) ==================

Route::prefix('supervisor')->middleware(['auth', 'role:Verifica_Supervisor|Verifica_Emisor|ASEM_Admin|OVAL_Admin'])->name('supervisor.')->group(function () {
    Route::get('/asignacion', \App\Livewire\Supervisor\AsignacionVerificacion::class)->name('asignacion');
    Route::get('/asignacion-complementaria', \App\Livewire\Supervisor\AsignacionComplementaria::class)->name('asignacion-complementaria');
    Route::get('/descarga-masiva', \App\Livewire\Shared\DescargaMasivaVerificacion::class)->name('descarga-masiva');
});

Route::get('/certificado/visor/{carpeta_id}', [\App\Http\Controllers\CertificadoController::class, 'generarCertificado'])
    ->name('verificacion.certificado.visor')
    ->middleware(['auth', 'role:Verifica_Supervisor|Verifica_Emisor|ASEM_Admin|OVAL_Admin|Verifica_Auditor|Contratista_Admin|Contratista_User|Subcontratista|Mandante_Admin|Mandante_Ver']);

Route::prefix('analista')->middleware(['auth', 'role:Verifica_Analista|ASEM_Admin|OVAL_Admin'])->name('analista.')->group(function () {
    Route::get('/mis-asignaciones', \App\Livewire\Analista\MisAsignaciones::class)->name('mis-asignaciones');
    Route::get('/descarga-masiva', \App\Livewire\Shared\DescargaMasivaVerificacion::class)->name('descarga-masiva');
});

Route::prefix('auditor')->middleware(['auth', 'role:Verifica_Auditor|ASEM_Admin|OVAL_Admin'])->name('auditor.')->group(function () {
    Route::get('/mis-auditorias', \App\Livewire\Auditor\MisAuditorias::class)->name('mis-auditorias');
    Route::get('/complementarios', \App\Livewire\Auditor\GestionComplementarios::class)->name('complementarios');
    Route::get('/descarga-masiva', \App\Livewire\Shared\DescargaMasivaVerificacion::class)->name('descarga-masiva');
});

Route::prefix('ia')->middleware(['auth', 'role:Operador_IA|ASEM_Admin'])->name('ia.')->group(function () {
    Route::get('/extraccion', \App\Livewire\OperadorIA\ControlExtraccion::class)->name('extraccion');
});

Route::get('/gestion/documentos/consulta', ListarNombreDocumentos::class)
    ->middleware(['auth', 'role:ASEM_Admin|Contratista_Admin'])
    ->name('gestion.documentos.consulta');

use App\Http\Controllers\OvalController;

Route::middleware('auth')->group(function () {
    Route::get('/oval/login', [OvalController::class, 'login'])->name('oval.login');
});

Route::prefix('oval')->middleware(['auth', 'role:OVAL_Admin|ASEM_Admin'])->name('oval.')->group(function () {
    Route::get('/control-acceso', \App\Livewire\Oval\ControlAcceso::class)->name('control-acceso');
    Route::get('/dashboard-verificacion', \App\Livewire\Oval\DashboardVerificacion::class)->name('dashboard-verificacion');
    
    // Rutas para Importador Histórico (Excel)
    Route::get('/importador-historico', [\App\Http\Controllers\ImportarCertificadoHistoricoController::class, 'index'])->name('importador-historico');
    Route::post('/importador-historico/procesar', [\App\Http\Controllers\ImportarCertificadoHistoricoController::class, 'procesar'])->name('importador-historico.procesar');
    Route::get('/importador-historico/plantilla', [\App\Http\Controllers\ImportarCertificadoHistoricoController::class, 'descargarPlantilla'])->name('importador-historico.plantilla');
    Route::post('/importador-historico/procesar-pdfs', [\App\Http\Controllers\ImportarCertificadoHistoricoController::class, 'procesarPdfs'])->name('importador-historico.procesar-pdfs');
});

require __DIR__.'/auth.php';

// DEBUG: Ruta temporal para diagnóstico de verificación
require __DIR__.'/debug_verificacion.php';

Route::get('/debug-uo', function () {
    // ID del contratista MADESUN SA (77.777.777-7)
    $contratista = \App\Models\Contratista::where('rut', '77.777.777-7')->first();

    if (!$contratista) {
        return "Contratista no encontrado.";
    }

    $output = "<h1>Contratista: " . $contratista->razon_social . " (ID: " . $contratista->id . ")</h1>";

    // Buscar UO Abastecimiento
    $uoAbastecimiento = \App\Models\UnidadOrganizacionalMandante::where('nombre_unidad', 'like', '%ABASTECIMIENTO%')->first();

    if ($uoAbastecimiento) {
        $output .= "<h2>UO Abastecimiento encontrada: " . $uoAbastecimiento->nombre_jerarquico . " (ID: " . $uoAbastecimiento->id . ")</h2>";
        
        // Verificar tabla pivote
        $existe = \Illuminate\Support\Facades\DB::table('contratista_unidad_organizacional')
            ->where('contratista_id', $contratista->id)
            ->where('unidad_organizacional_mandante_id', $uoAbastecimiento->id)
            ->first();
            
        if ($existe) {
            $output .= "<p style='color:green'>✅ RELACIÓN EXISTE en tabla pivote.<br>";
            $output .= "Datos: " . json_encode($existe) . "</p>";
        } else {
            $output .= "<p style='color:red'>❌ RELACIÓN NO EXISTE en tabla pivote. El contratista NO tiene asignada esta UO.</p>";
            
            // Ver si hay CUALQUIER otra con ID parecido (por si acaso)
            $similares = \Illuminate\Support\Facades\DB::table('contratista_unidad_organizacional')
                ->where('contratista_id', $contratista->id)
                ->get();
            $output .= "<p>El contratista tiene " . $similares->count() . " relaciones de UO en total:</p><ul>";
            foreach($similares as $sim) {
                $uo = \App\Models\UnidadOrganizacionalMandante::find($sim->unidad_organizacional_mandante_id);
                $output .= "<li>UO ID: " . $sim->unidad_organizacional_mandante_id . " (" . ($uo?->nombre_jerarquico ?? 'N/A') . ") - Contrato: " . ($sim->numero_contrato ?? 'NULL') . "</li>";
            }
            $output .= "</ul>";
        }

    } else {
        $output .= "<h2>UO Abastecimiento no encontrada en la BD.</h2>";
    }
    
    return $output;
});
