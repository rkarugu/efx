<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\User;

echo "Check Current User Role\n";
echo "=======================\n\n";

// Get the authenticated user (simulating web request)
// In production, check who is logged in via the web interface

echo "All Admin Users:\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s | %-30s | %-15s | %-10s | %-30s\n", "ID", "Name", "Email", "Role ID", "Branch ID");
echo str_repeat("-", 100) . "\n";

$users = User::whereIn('role_id', [1, 2, 3])->get();

foreach ($users as $user) {
    $roleName = 'Unknown';
    switch ($user->role_id) {
        case 1: $roleName = 'Super Admin'; break;
        case 2: $roleName = 'Admin'; break;
        case 3: $roleName = 'Manager'; break;
    }
    
    printf("%-5s | %-30s | %-15s | %-10s (%-13s) | %-10s\n", 
        $user->id, 
        substr($user->name, 0, 30), 
        substr($user->email ?? 'N/A', 0, 15),
        $user->role_id,
        $roleName,
        $user->restaurant_id ?? 'N/A'
    );
}

echo str_repeat("-", 100) . "\n\n";

echo "To check a specific user, provide their ID as argument:\n";
echo "php check_user_role.php [user_id]\n";
