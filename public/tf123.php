<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());
use App\Models\CarpetaVerificacion;
use App\Models\DocumentoVerificacion;
$mid = 5; 
$req_id = 1;
$carpetasQ = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
    ->whereHas('vinculacion', function ($q) use ($mid) {
        $q->where(function ($sub) use ($mid) {
            $sub->whereHas('unidadOrganizacionalMandante', fn($q2) => $q2->where('mandante_id', $mid))
                ->orWhereHas('dependencia', fn($q2) => $q2->where('mandante_id', $mid));
        });
    })
    ->where('anio', 2025)
    ->where('mes', 12);
$carpetasId = $carpetasQ->pluck('id');
echo "Carpetas: " . $carpetasId->implode(',') . "\n";

$documentos = DocumentoVerificacion::with('carpeta.vinculacion')
    ->whereIn('carpeta_verificacion_id', $carpetasId)
    ->where('requisito_verificacion_id', $req_id)
    ->whereNotNull('path')
    ->get()
    ->filter(function ($doc) {
        return $doc->carpeta && $doc->carpeta->vinculacion;
    });
echo "Documentos Req 1: " . $documentos->count() . "\n";
