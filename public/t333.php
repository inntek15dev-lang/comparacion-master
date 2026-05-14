<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use App\Models\CarpetaVerificacion;
use App\Models\DocumentoVerificacion;

$carpetasQ = CarpetaVerificacion::where('anio', 2025)->where('mes', 12);
$carpetasId = $carpetasQ->pluck('id');

$documentos = DocumentoVerificacion::with('carpeta.vinculacion')
    ->whereIn('carpeta_verificacion_id', $carpetasId)
    ->whereNotNull('path')
    ->get();

echo "Documentos Opcache Bypassed:\n";
foreach($documentos as $d) {
    echo "- Doc {$d->id} -> Carpeta {$d->carpeta_verificacion_id}\n";
}
