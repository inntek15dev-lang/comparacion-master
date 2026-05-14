<?php
/**
 * DIAGNÓSTICO: Descarga Masiva - Por qué no encuentra documentos
 * Ejecutar: http://localhost:8000/diag_descarga.php  (o moverlo a /public)
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CarpetaVerificacion;
use App\Models\Mandante;
use App\Models\RequisitoVerificacion;
use App\Models\DocumentoVerificacion;
use Illuminate\Support\Facades\DB;

echo "<style>body{font-family:monospace;font-size:13px;padding:20px;background:#1a1a2e;color:#e0e0e0}
h2{color:#4fc3f7;border-bottom:1px solid #333;padding-bottom:5px}
h3{color:#81c784}
.ok{color:#66bb6a} .warn{color:#ffa726} .err{color:#ef5350}
table{border-collapse:collapse;width:100%;margin:10px 0}
td,th{border:1px solid #333;padding:5px 8px;text-align:left}
th{background:#263238;color:#4fc3f7}
tr:hover{background:#1e2a35}
pre{background:#0d1117;padding:10px;border-radius:4px;overflow-x:auto;color:#aaa}
</style>";

// ============================================================
// PASO 1: Mandantes disponibles
// ============================================================
echo "<h2>PASO 1: Mandantes activos en el sistema</h2>";
$mandantes = Mandante::where('is_active', true)->get();
echo "<table><tr><th>ID</th><th>Razón Social</th></tr>";
foreach ($mandantes as $m) {
    echo "<tr><td>{$m->id}</td><td>{$m->razon_social}</td></tr>";
}
echo "</table>";

// ============================================================
// PASO 2: Carpetas Nov 2025
// ============================================================
echo "<h2>PASO 2: Carpetas de verificación - Noviembre 2025</h2>";
$carpetas = CarpetaVerificacion::with([
    'vinculacion.contratista',
    'vinculacion.unidadOrganizacional.mandante',
    'vinculacion.dependencia',
])->where('anio', 2025)->where('mes', 12)->get();

echo "<p class='".($carpetas->count() > 0 ? 'ok' : 'err')."'>Total carpetas Nov 2025: <strong>{$carpetas->count()}</strong></p>";

if ($carpetas->count() > 0) {
    echo "<table>
    <tr>
        <th>ID Carpeta</th><th>Contratista</th><th>Estado</th><th>Estado Rev.</th>
        <th>UO ID</th><th>UO Mandante ID</th><th>Dep ID</th><th>Dep Mandante ID</th>
        <th>Pivot mandante_id (col?)</th>
    </tr>";
    
    foreach ($carpetas as $c) {
        $v = $c->vinculacion;
        $contratista = $v?->contratista?->razon_social ?? 'N/A';
        $uoId = $v?->unidad_organizacional_mandante_id ?? 'NULL';
        $uoMandante = $v?->unidadOrganizacional?->mandante_id ?? 'NULL';
        $depId = $v?->dependencia_id ?? 'NULL';
        $depMandante = $v?->dependencia?->mandante_id ?? 'NULL';
        
        // Check if mandante_id column exists on pivot
        $rawPivot = DB::table('contratista_unidad_organizacional')->where('id', $v?->id)->first();
        $pivotManId = isset($rawPivot->mandante_id) ? $rawPivot->mandante_id : '<span class="err">COL NO EXISTE</span>';
        
        $estadoClass = $c->estado === 'PENDIENTE' ? 'err' : 'ok';
        
        echo "<tr>
            <td>{$c->id}</td>
            <td>{$contratista}</td>
            <td class='{$estadoClass}'>{$c->estado}</td>
            <td>{$c->estado_revision}</td>
            <td>{$uoId}</td>
            <td>{$uoMandante}</td>
            <td>{$depId}</td>
            <td>{$depMandante}</td>
            <td>{$pivotManId}</td>
        </tr>";
    }
    echo "</table>";
}

// ============================================================
// PASO 3: Simular la query de descarga masiva para mandante=5, mes=11, 2025
// ============================================================
echo "<h2>PASO 3: Simulación query descarga masiva (mandante_id=5, Nov 2025)</h2>";
echo "<p class='warn'>Probando todos los mandantes activos...</p>";

foreach ($mandantes as $mandante) {
    $mid = $mandante->id;
    
    // Query con nueva lógica
    $carpetasQ = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
        ->whereHas('vinculacion', function ($q) use ($mid) {
            $q->where(function ($sub) use ($mid) {
                $sub->whereHas('unidadOrganizacionalMandante', fn($q2) => $q2->where('mandante_id', $mid))
                    ->orWhereHas('dependencia', fn($q2) => $q2->where('mandante_id', $mid));
            });
        })
        ->where('anio', 2025)
        ->where('mes', 12);
    
    $count = $carpetasQ->count();
    $class = $count > 0 ? 'ok' : 'warn';
    echo "<p class='{$class}'>Mandante <strong>[{$mid}] {$mandante->razon_social}</strong>: <strong>{$count}</strong> carpetas Nov/2025 (estado != PENDIENTE)</p>";
    
    if ($count > 0) {
        $ids = $carpetasQ->pluck('id');
        echo "<p class='ok'>→ IDs carpetas: " . $ids->implode(', ') . "</p>";
    }
}

// ============================================================
// PASO 4: Requisitos de verificación disponibles
// ============================================================
echo "<h2>PASO 4: Requisitos (Tipos de documento) para cada mandante</h2>";
foreach ($mandantes as $mandante) {
    $reqs = RequisitoVerificacion::where('mandante_id', $mandante->id)
        ->where('is_active', true)
        ->get(['id', 'nombre', 'codigo']);
    
    if ($reqs->count() > 0) {
        echo "<h3>Mandante [{$mandante->id}] {$mandante->razon_social}</h3><table>
            <tr><th>ID Req.</th><th>Nombre</th><th>Código</th></tr>";
        foreach ($reqs as $r) {
            $liq = stripos($r->nombre, 'liquidacion') !== false || stripos($r->nombre, 'sueldo') !== false;
            echo "<tr" . ($liq ? " class='ok'" : "") . "><td>{$r->id}</td><td>{$r->nombre}</td><td>{$r->codigo}</td></tr>";
        }
        echo "</table>";
    }
}

// ============================================================
// PASO 5: Documentos subidos para Nov 2025 (todos)
// ============================================================
echo "<h2>PASO 5: DocumentoVerificacion con path != NULL para Nov 2025</h2>";
$docs = DocumentoVerificacion::with([
    'carpeta.vinculacion.contratista',
    'carpeta.vinculacion.unidadOrganizacional',
    'requisito',
])
->whereNotNull('path')
->whereHas('carpeta', fn($q) => $q->where('anio', 2025)->where('mes', 12))
->get();

echo "<p class='".($docs->count() > 0 ? 'ok' : 'err')."'>Total documentos con path para Nov 2025: <strong>{$docs->count()}</strong></p>";

if ($docs->count() > 0) {
    echo "<table>
    <tr><th>Doc ID</th><th>Req ID</th><th>Requisito</th><th>Contratista</th><th>Estado Carpeta</th><th>UO mandante_id</th><th>Path</th></tr>";
    foreach ($docs as $d) {
        $carp = $d->carpeta;
        $v    = $carp?->vinculacion;
        echo "<tr>
            <td>{$d->id}</td>
            <td>{$d->requisito_verificacion_id}</td>
            <td>{$d->requisito?->nombre}</td>
            <td>{$v?->contratista?->razon_social}</td>
            <td class='".($carp?->estado === 'PENDIENTE' ? 'err' : 'ok')."'>{$carp?->estado}</td>
            <td>{$v?->unidadOrganizacional?->mandante_id}</td>
            <td style='font-size:10px'>{$d->path}</td>
        </tr>";
    }
    echo "</table>";
}

// ============================================================
// PASO 6: SQL real generado
// ============================================================
echo "<h2>PASO 6: SQL real de la query para mandante_id=5, Nov 2025</h2>";
foreach ($mandantes as $mandante) {
    $mid = $mandante->id;
    $sql = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
        ->whereHas('vinculacion', function ($q) use ($mid) {
            $q->where(function ($sub) use ($mid) {
                $sub->whereHas('unidadOrganizacionalMandante', fn($q2) => $q2->where('mandante_id', $mid))
                    ->orWhereHas('dependencia', fn($q2) => $q2->where('mandante_id', $mid));
            });
        })
        ->where('anio', 2025)
        ->where('mes', 12)
        ->toSql();
    
    echo "<h3>Mandante [{$mid}] {$mandante->razon_social}</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    //break; // Solo mostrar uno
}

echo "<hr><p class='ok'>✅ Diagnóstico completo.</p>";
