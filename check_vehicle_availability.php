<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Vehicle;
use App\DeliverySchedule;

echo "Vehicle Availability Diagnostic\n";
echo "================================\n\n";

// Get current user's branch (adjust this for your production branch)
// Common branch IDs: 1 (main), 10 (production)
$branchId = isset($argv[1]) ? (int)$argv[1] : 10;
echo "Checking for branch ID: {$branchId}\n";
echo "Usage: php check_vehicle_availability.php [branch_id]\n\n";

// Get all vehicles with drivers in the branch
$vehicles = Vehicle::with(['driver'])
    ->whereHas('driver')
    ->where('branch_id', $branchId)
    ->get();

echo "Total vehicles with drivers in branch: " . $vehicles->count() . "\n\n";

if ($vehicles->count() === 0) {
    echo "❌ No vehicles found with drivers in branch {$branchId}\n";
    echo "Possible issues:\n";
    echo "  1. Wrong branch_id (try different branch ID)\n";
    echo "  2. No vehicles have drivers assigned\n";
    echo "  3. Vehicles are in a different branch\n\n";
    
    // Show all vehicles
    echo "All vehicles in database:\n";
    $allVehicles = Vehicle::with('driver')->get();
    
    // Group by branch
    $byBranch = $allVehicles->groupBy('branch_id');
    foreach ($byBranch as $branch => $branchVehicles) {
        $withDrivers = $branchVehicles->filter(function($v) { return $v->driver !== null; });
        echo "\nBranch {$branch}: {$branchVehicles->count()} vehicles, {$withDrivers->count()} with drivers\n";
        foreach ($withDrivers as $v) {
            echo "  - ID: {$v->id}, Plate: {$v->license_plate_number}, Driver: {$v->driver->name}\n";
        }
    }
    exit;
}

echo "Vehicles in branch {$branchId}:\n";
echo str_repeat("-", 80) . "\n";

$availableCount = 0;

foreach ($vehicles as $vehicle) {
    echo "Vehicle ID: {$vehicle->id}\n";
    echo "  Name: " . ($vehicle->name ?: 'N/A') . "\n";
    echo "  License Plate: {$vehicle->license_plate_number}\n";
    echo "  Driver: " . ($vehicle->driver ? $vehicle->driver->name : 'None') . "\n";
    echo "  Branch ID: {$vehicle->branch_id}\n";
    
    // Check for active schedules
    $activeSchedules = DeliverySchedule::where('vehicle_id', $vehicle->id)
        ->where('status', '!=', 'finished')
        ->get();
    
    echo "  Active Schedules (status != 'finished'): " . $activeSchedules->count() . "\n";
    
    if ($activeSchedules->count() > 0) {
        foreach ($activeSchedules as $schedule) {
            echo "    - Schedule ID: {$schedule->id}, Status: {$schedule->status}, Created: {$schedule->created_at}\n";
        }
        echo "  ❌ UNAVAILABLE (has active schedules)\n";
    } else {
        echo "  ✅ AVAILABLE (no active schedules)\n";
        $availableCount++;
    }
    
    echo str_repeat("-", 80) . "\n";
}

// Summary
echo "\nSummary:\n";
echo "  Total vehicles: {$vehicles->count()}\n";
echo "  Available vehicles: {$availableCount}\n";
echo "  Unavailable vehicles: " . ($vehicles->count() - $availableCount) . "\n\n";

if ($availableCount === 0) {
    echo "⚠️  All vehicles are assigned to active delivery schedules!\n";
    echo "To make vehicles available, finish their active schedules.\n\n";
} else {
    echo "✅ {$availableCount} vehicle(s) should appear in the dropdown.\n\n";
}

// Check what the API would return
echo "API Response Simulation:\n";
echo "------------------------\n";
$apiResponse = [];
foreach ($vehicles as $vehicle) {
    $activeSchedule = DeliverySchedule::where('vehicle_id', $vehicle->id)
        ->where('status', '!=', 'finished')
        ->first();
    
    if (!$activeSchedule) {
        $apiResponse[] = [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'license_plate_number' => $vehicle->license_plate_number,
            'driver_name' => $vehicle->driver ? $vehicle->driver->name : 'No Driver',
            'display_name' => $vehicle->name . ' ' . $vehicle->license_plate_number . ' (' . ($vehicle->driver ? $vehicle->driver->name : 'No Driver') . ')'
        ];
    }
}

echo json_encode(['data' => $apiResponse], JSON_PRETTY_PRINT) . "\n";
