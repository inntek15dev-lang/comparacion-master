<?php

use Illuminate\Support\Facades\Route;
use App\Models\CalendarioVerificacion;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CarpetaVerificacion;
use App\Models\DocumentoVerificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

Route::get('/debug-verificacion', function () {
    $hoy = Carbon::now()->toDateString();
    
    echo "<h1>DEBUG VERIFICACIÓN - HOY: {$hoy}</h1>";
    
    // 1. Mostrar todos los calendarios
    echo "<h2>CALENDARIOS DE VERIFICACIÓN</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Mandante</th><th>Mes</th><th>Año</th><th>Apertura</th><th>Cierre</th><th>¿Visible?</th></tr>";
    
    $calendarios = CalendarioVerificacion::orderBy('anio', 'desc')->orderBy('mes', 'desc')->get();
    foreach ($calendarios as $cal) {
        $aperturaStr = $cal->fecha_apertura ? $cal->fecha_apertura->format('Y-m-d') : 'NULL';
        $visible = $hoy >= $aperturaStr ? '✅ SÍ' : '❌ NO (apertura futura)';
        echo "<tr>
            <td>{$cal->id}</td>
            <td>{$cal->mandante_id}</td>
            <td>{$cal->mes}</td>
            <td>{$cal->anio}</td>
            <td>{$aperturaStr}</td>
            <td>" . ($cal->fecha_cierre ? $cal->fecha_cierre->format('Y-m-d') : 'NULL') . "</td>
            <td>{$visible}</td>
        </tr>";
    }
    echo "</table>";
    
    // 2. Mostrar vinculaciones de MADESUN
    echo "<h2>VINCULACIONES CON VERIFICA=TRUE</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Contratista</th><th>Mandante</th><th>Inicio Verifica</th><th>Fin Verifica</th></tr>";
    
    $vinculaciones = ContratistaUnidadOrganizacional::where('verifica', true)
        ->with(['contratista', 'unidadOrganizacional.mandante'])
        ->get();
    
    foreach ($vinculaciones as $v) {
        echo "<tr>
            <td>{$v->id}</td>
            <td>" . ($v->contratista->razon_social ?? 'N/A') . "</td>
            <td>" . ($v->unidadOrganizacional->mandante->razon_social ?? 'N/A') . "</td>
            <td>" . ($v->fecha_inicio_verifica ? $v->fecha_inicio_verifica->format('Y-m-d') : 'NULL') . "</td>
            <td>" . ($v->fecha_fin_verifica ? $v->fecha_fin_verifica->format('Y-m-d') : 'NULL') . "</td>
        </tr>";
    }
    echo "</table>";
    
    // 3. Mostrar carpetas y documentos
    echo "<h2>CARPETAS Y DOCUMENTOS</h2>";
    $carpetas = CarpetaVerificacion::with('documentos')->get();
    foreach ($carpetas as $c) {
        echo "<h3>Carpeta #{$c->id} - Vinc: {$c->contratista_unidad_organizacional_id} - {$c->mes}/{$c->anio} - Estado: {$c->estado}</h3>";
        echo "<ul>";
        foreach ($c->documentos as $doc) {
            $exists = Storage::disk('public')->exists($doc->path) ? '✅' : '❌ NO EXISTE';
            echo "<li>Doc #{$doc->id}: {$doc->nombre_original} - Path: {$doc->path} - {$exists}</li>";
        }
        if ($c->documentos->isEmpty()) {
            echo "<li><em>Sin documentos</em></li>";
        }
        echo "</ul>";
    }
    
    // 4. Info de Storage
    echo "<h2>CONFIGURACIÓN DE STORAGE</h2>";
    echo "<p>Disk default: " . config('filesystems.default') . "</p>";
    echo "<p>Disk public path: " . Storage::disk('public')->path('') . "</p>";
    echo "<p>Storage link exists: " . (file_exists(public_path('storage')) ? '✅ SÍ' : '❌ NO - Ejecutar php artisan storage:link') . "</p>";
    
    return '';
});

// Ruta para corregir TODAS las fechas de apertura
// Regla: El periodo del mes X se abre el 1 del mes X+1
Route::get('/fix-calendarios', function () {
    $calendarios = CalendarioVerificacion::all();
    $cambios = [];
    
    foreach ($calendarios as $cal) {
        // Calcular la fecha de apertura correcta: día 1 del mes siguiente al periodo
        $mesSiguiente = $cal->mes + 1;
        $anioApertura = $cal->anio;
        
        if ($mesSiguiente > 12) {
            $mesSiguiente = 1;
            $anioApertura++;
        }
        
        $fechaAperturaCorrecta = Carbon::create($anioApertura, $mesSiguiente, 1);
        $anterior = $cal->fecha_apertura ? $cal->fecha_apertura->format('Y-m-d') : 'NULL';
        
        if ($anterior !== $fechaAperturaCorrecta->format('Y-m-d')) {
            $cal->update(['fecha_apertura' => $fechaAperturaCorrecta]);
            $cambios[] = "Periodo {$cal->mes}/{$cal->anio}: {$anterior} -> {$fechaAperturaCorrecta->format('Y-m-d')}";
        }
    }
    
    if (empty($cambios)) {
        return "<h2>Todos los calendarios ya tienen fechas correctas</h2>";
    }
    
    $html = "<h2>Calendarios Corregidos</h2><ul>";
    foreach ($cambios as $c) {
        $html .= "<li>{$c}</li>";
    }
    $html .= "</ul><p><a href='/debug-verificacion'>Ver Debug</a></p>";
    
    return $html;
});

// Ruta para migrar carpetas de CARGADO a EN PROGRESO
Route::get('/fix-estados', function () {
    $actualizados = CarpetaVerificacion::where('estado', 'CARGADO')
        ->update(['estado' => 'EN PROGRESO']);
    
    return "Carpetas actualizadas: {$actualizados} (de CARGADO a EN PROGRESO)";
});

// Ruta para ver y corregir el orden de las clasificaciones
Route::get('/fix-clasificaciones', function () {
    $clasificaciones = \App\Models\ClasificacionVerificacion::orderBy('orden')->get();
    
    echo "<h2>CLASIFICACIONES DE VERIFICACIÓN (antes de corrección)</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Orden</th></tr>";
    foreach ($clasificaciones as $c) {
        echo "<tr><td>{$c->id}</td><td>{$c->nombre}</td><td>{$c->orden}</td></tr>";
    }
    echo "</table>";
    
    // Actualizar orden basado en palabras clave
    \App\Models\ClasificacionVerificacion::where('nombre', 'LIKE', '%PAGO AL TRABAJADOR%')
        ->update(['orden' => 1]);
        
    \App\Models\ClasificacionVerificacion::where('nombre', 'LIKE', '%INSTITUCIONES PREVISIONALES%')
        ->update(['orden' => 2]);
        
    \App\Models\ClasificacionVerificacion::where('nombre', 'LIKE', '%OTROS%')
        ->orWhere('nombre', 'LIKE', '%LICENCIAS%')
        ->orWhere('nombre', 'LIKE', '%FINIQUITOS%')
        ->update(['orden' => 3]);
    
    $clasificaciones = \App\Models\ClasificacionVerificacion::orderBy('orden')->get();
    
    echo "<h2>CLASIFICACIONES DE VERIFICACIÓN (después de corrección)</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Orden</th></tr>";
    foreach ($clasificaciones as $c) {
        echo "<tr><td>{$c->id}</td><td>{$c->nombre}</td><td>{$c->orden}</td></tr>";
    }
    echo "</table>";
    
    echo "<p><a href='/contratista/verificacion'>Ir a Verificación</a></p>";
    
    return '';
});
