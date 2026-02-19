<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Model\WaShift;

$shift = WaShift::where('salesman_id', 655)
    ->where('status', 'open')
    ->orderByDesc('id')
    ->first();

if (!$shift) {
    echo "No open shifts for salesman 655.\n";
    exit(0);
}

echo json_encode($shift->toArray(), JSON_PRETTY_PRINT) . "\n";
