<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

try {
    $roleName = 'Operador_IA';
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    
    echo "ROLE CREATED: " . $role->name . " (ID: " . $role->id . ")\n";
    
    // Optional: Assign some basic permissions if needed, 
    // but the role check in the component will be enough for now.
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
