<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $ia = app(App\Services\IaExtraccionService::class);
    $doc = App\Models\DocumentoCargado::find(118);
    $ia->procesarDocumento($doc);
    echo "Success";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString();
}
