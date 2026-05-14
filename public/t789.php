<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
use App\Models\CarpetaVerificacion;

$c = CarpetaVerificacion::with('vinculacion.unidadOrganizacionalMandante', 'vinculacion.dependencia')->find(38);
echo "Carpeta 38:\n";
echo "Vinculacion ID: {$c->contratista_unidad_organizacional_id}\n";
echo "UO Mandante ID: " . optional($c->vinculacion->unidadOrganizacionalMandante)->mandante_id . "\n";
echo "Dep Mandante ID: " . optional($c->vinculacion->dependencia)->mandante_id . "\n";

$c31 = CarpetaVerificacion::with('vinculacion.unidadOrganizacionalMandante', 'vinculacion.dependencia')->find(31);
echo "Carpeta 31:\n";
echo "Vinculacion ID: {$c31->contratista_unidad_organizacional_id}\n";
echo "UO Mandante ID: " . optional($c31->vinculacion->unidadOrganizacionalMandante)->mandante_id . "\n";
echo "Dep Mandante ID: " . optional($c31->vinculacion->dependencia)->mandante_id . "\n";
