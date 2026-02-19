<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Vehicle;

echo "Delete Branch 10 Vehicles Script\n";
echo "=================================\n\n";

$branchId = 10;

// Get all vehicles in branch 10
$vehicles = Vehicle::where('branch_id', $branchId)->get();

echo "Found " . $vehicles->count() . " vehicles in branch {$branchId}\n\n";

if ($vehicles->count() === 0) {
    echo "No vehicles to delete.\n";
    exit;
}

// Show vehicles to be deleted
echo "Vehicles to be deleted:\n";
echo str_repeat("-", 80) . "\n";
foreach ($vehicles as $vehicle) {
    echo "ID: {$vehicle->id} | Plate: {$vehicle->license_plate_number} | Name: " . ($vehicle->name ?: 'N/A') . "\n";
}
echo str_repeat("-", 80) . "\n\n";

// Ask for confirmation
echo "⚠️  WARNING: This will permanently delete " . $vehicles->count() . " vehicles from branch {$branchId}!\n";
echo "This action CANNOT be undone.\n\n";
echo "Type 'DELETE' to confirm: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'DELETE') {
    echo "\n❌ Deletion cancelled. No vehicles were deleted.\n";
    exit;
}

echo "\n🗑️  Deleting vehicles...\n\n";

DB::beginTransaction();

try {
    $deletedCount = 0;
    
    foreach ($vehicles as $vehicle) {
        // Check if vehicle is assigned to any delivery schedules
        $scheduleCount = DB::table('delivery_schedules')
            ->where('vehicle_id', $vehicle->id)
            ->count();
        
        if ($scheduleCount > 0) {
            echo "⚠️  Skipping vehicle {$vehicle->id} ({$vehicle->license_plate_number}) - assigned to {$scheduleCount} delivery schedule(s)\n";
            continue;
        }
        
        // Delete the vehicle
        $vehicle->delete();
        $deletedCount++;
        echo "✅ Deleted vehicle {$vehicle->id} ({$vehicle->license_plate_number})\n";
    }
    
    DB::commit();
    
    echo "\n✅ Successfully deleted {$deletedCount} vehicle(s) from branch {$branchId}\n";
    
    if ($deletedCount < $vehicles->count()) {
        $skipped = $vehicles->count() - $deletedCount;
        echo "⚠️  Skipped {$skipped} vehicle(s) that were assigned to delivery schedules\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error deleting vehicles: " . $e->getMessage() . "\n";
    echo "No vehicles were deleted.\n";
}
