<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$columns = DB::select('DESCRIBE carpetas_verificacion');
$found = false;
foreach($columns as $col) {
    if ($col->Field === 'ia_datos_extraidos') {
        $found = true;
        echo "COLUMN FOUND: " . $col->Field . " - " . $col->Type . "\n";
    }
}

if (!$found) {
    echo "COLUMN NOT FOUND in carpetas_verificacion.\n";
}
