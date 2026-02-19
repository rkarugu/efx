<?php
/**
 * Script to close all open shifts
 * Run: php close_shifts.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\SalesmanShift;
use Carbon\Carbon;

echo "Closing all open shifts...\n\n";

// Get all open shifts
$openShifts = SalesmanShift::where('status', 'open')->get();

if ($openShifts->isEmpty()) {
    echo "No open shifts found.\n";
    exit(0);
}

echo "Found " . $openShifts->count() . " open shift(s):\n";

foreach ($openShifts as $shift) {
    echo "\nShift ID: {$shift->id}\n";
    echo "Salesman: {$shift->salesman->name}\n";
    echo "Route: {$shift->route}\n";
    echo "Opened at: {$shift->start_time}\n";
    
    // Close the shift
    $shift->status = 'close';
    $shift->closed_time = Carbon::now();
    $shift->save();
    
    echo "✓ Shift closed successfully!\n";
}

echo "\n✓ All shifts have been closed.\n";
