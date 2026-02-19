<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Model\WaShift;
use Carbon\Carbon;

/** @var WaShift|null $shift */
$shift = WaShift::where('route_name', 'Thika Town CBD')
    ->where('status', 'open')
    ->orderByDesc('id')
    ->first();

if (!$shift) {
    echo "No open Thika Town CBD shifts found.\n";
    exit(0);
}

echo "Closing shift ID {$shift->id} started at {$shift->shift_start_time}\n";
$shift->status = 'close';
$shift->shift_close_time = Carbon::now();
$shift->block_orders = 1;
$shift->save();

echo "Shift closed.\n";
