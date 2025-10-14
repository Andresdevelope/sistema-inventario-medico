<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
// Forzar carga .env y DB
$kernel->bootstrap();
use App\Models\Proveedor;
$all = Proveedor::select('id','nombre','contacto','email')->get();
echo $all->toJson(JSON_PRETTY_PRINT), "\n";
