<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\CarpetaVerificacion;
use App\Models\DocumentoVerificacion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Supongamos que descargamos la carpeta ID = 31 y 38
$carpetasId = [31, 38];

$documentos = DocumentoVerificacion::with([
    'carpeta.vinculacion.contratista',
    'carpeta.vinculacion.dependencia',
    'carpeta.vinculacion.unidadOrganizacionalMandante.mandante',
    'requisito'
])
->whereIn('carpeta_verificacion_id', $carpetasId)
->whereNotNull('path')
->get();

if ($documentos->isEmpty()) {
    echo "BD: No hay registros de documentos en la base de datos para ID 31 y 38.\n";
    exit;
}

echo "Registros encontrados en BD: " . $documentos->count() . "\n\n";

$zip = new ZipArchive();
$zipFileName = 'test_descarga_contextual_' . date('Ymd_His') . '.zip';
$zipPath = storage_path('app/public/' . $zipFileName);

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $archivosAgregados = 0;
    $contadorArchivos = []; 

    $sanitizar = function(string $valor): string {
        $from = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'];
        $to   = ['a','e','i','o','u','u','n','A','E','I','O','U','U','N'];
        $valor = str_replace($from, $to, $valor);
        $valor = preg_replace('/[^A-Za-z0-9\-]/', '_', $valor);
        $valor = preg_replace('/_+/', '_', $valor);
        return strtoupper(trim($valor, '_'));
    };

    foreach ($documentos as $doc) {
        $realPath = Storage::disk('local')->path($doc->path);

        if (!file_exists($realPath)) {
            $realPathPublic = Storage::disk('public')->path($doc->path);
            if (file_exists($realPathPublic)) {
                $realPath = $realPathPublic;
            }
        }

        $existeFisico = file_exists($realPath) ? 'SI' : 'NO';
        echo "- Doc ID: {$doc->id} | Carpeta: {$doc->carpeta_verificacion_id} | Req: ".($doc->requisito->nombre ?? 'N/A')." | Físico: $existeFisico | Path BD: {$doc->path}\n";
        
        if ($existeFisico === 'SI') {
            $carpeta     = $doc->carpeta;
            $vinculacion = $carpeta->vinculacion;
            $contratista = $vinculacion->contratista;

            if (!$contratista) {
                echo "    -> ERROR: No hay contratista vinculado\n";
                continue;
            }

            $requisitoGroup = $doc->requisito ? $sanitizar($doc->requisito->nombre) : 'OTROS_DOCUMENTOS';
            $codigoDoc      = $doc->requisito ? ($doc->requisito->codigo ?: 'DOC') : 'DOC';

            $idRegistro  = $vinculacion->id_registro ?: $contratista->id;
            $mandanteRaw = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'SIN_PRINCIPAL';
            $mandante = $sanitizar($mandanteRaw);
            $lugarRaw = $vinculacion->dependencia?->nombre ?? 'SIN_LUGAR';
            $lugar = $sanitizar($lugarRaw);
            $contratoRaw = $vinculacion->numero_contrato ?: 'SC';
            $contrato = $sanitizar((string) $contratoRaw);
            $mesPad = str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT);
            $periodo = "{$mesPad}_{$carpeta->anio}";

            $keyContador = "{$requisitoGroup}_{$vinculacion->id}_{$periodo}";
            if (!isset($contadorArchivos[$keyContador])) $contadorArchivos[$keyContador] = 1;
            else $contadorArchivos[$keyContador]++;
            $x = $contadorArchivos[$keyContador];

            $ext = pathinfo($realPath, PATHINFO_EXTENSION);
            if (!$ext && $doc->nombre_original) $ext = pathinfo($doc->nombre_original, PATHINFO_EXTENSION);
            $ext = $ext ? strtoupper($ext) : 'PDF';

            $nombreArchivo = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$x}.{$ext}");
            $pathEnZip = $requisitoGroup . '/' . $nombreArchivo;
            
            $zip->addFile($realPath, $pathEnZip);
            echo "    -> AGREGADO a ZIP: $pathEnZip\n";
            $archivosAgregados++;
        }
    }
    $zip->close();
    echo "\nResumen: Se agregaron {$archivosAgregados} archivos al zip: {$zipPath}\n";
} else {
    echo "Fallo al crear ZIP.\n";
}
