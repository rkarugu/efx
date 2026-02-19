<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\User;
use App\UserLinkedDevice;

$user = User::where('email', 'sales@efficentrix.co.ke')
    ->orWhere('phone_number', 'sales@efficentrix.co.ke')
    ->first();

if (!$user) {
    echo "User not found\n";
    exit(1);
}

echo "User ID: {$user->id}\n";

echo "Linked devices:\n";
$devices = UserLinkedDevice::where('user_id', $user->id)->get();
if ($devices->isEmpty()) {
    echo "  (none)\n";
    exit(0);
}

foreach ($devices as $device) {
    echo "  ID={$device->id}, device_id={$device->device_id}, created_at={$device->created_at}\n";
}
