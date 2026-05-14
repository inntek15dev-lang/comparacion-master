<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CarpetaVerificacion;
use App\Models\DocumentoVerificacion;

echo "<style>body{background:#111;color:#eee;font-family:monospace}</style>";

$mandante_id = 5;
$requisito_id = 1;
$anio = 2025;
$mes = 11; // Noviembre en el dropdown

$carpetasQuery = CarpetaVerificacion::where('estado', '!=', 'PENDIENTE')
    ->whereHas('vinculacion', function ($q) use ($mandante_id) {
        $q->where(function ($sub) use ($mandante_id) {
            $sub->whereHas('unidadOrganizacional', fn ($q2) => $q2->where('mandante_id', $mandante_id))
                ->orWhereHas('dependencia', fn ($q2) => $q2->where('mandante_id', $mandante_id));
        });
    });

$carpetasQuery->where('anio', $anio)->where('mes', $mes);

$carpetasId = $carpetasQuery->pluck('id');

echo "Carpetas encontradas: " . $carpetasId->count() . "<br>";
echo "IDs: " . json_encode($carpetasId) . "<br><br>";

$documentosQuery = DocumentoVerificacion::with('carpeta.vinculacion')
    ->whereIn('carpeta_verificacion_id', $carpetasId)
    ->where('requisito_verificacion_id', $requisito_id)
    ->whereNotNull('path');

$docsRaw = $documentosQuery->get();
echo "Documentos Raw Count: " . $docsRaw->count() . "<br>";

$docsFiltered = $docsRaw->filter(function ($doc) {
    return $doc->carpeta && $doc->carpeta->vinculacion;
});

echo "Documentos Filtered Count: " . $docsFiltered->count() . "<br>";

echo "<br><b>Valores del Request simulando Livewire:</b><br>";
echo "Mes var_dump: "; var_dump($mes);
echo "<br>Requisito ID var_dump: "; var_dump($requisito_id);
