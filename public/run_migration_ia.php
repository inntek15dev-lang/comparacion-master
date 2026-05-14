<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

try {
    if (!Schema::hasColumn('carpetas_verificacion', 'ia_datos_extraidos')) {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->boolean('ia_datos_extraidos')->default(false)->after('estado_revision');
        });
        echo "MIGRATION SUCCESS: Column ia_datos_extraidos added.\n";
    } else {
        echo "MIGRATION SKIPPED: Column already exists.\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
