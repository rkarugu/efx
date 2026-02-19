<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

$user = App\User::where('email', 'sales@efficentrix.co.ke')->first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

$result = Hash::check('REDACTED_PASSWORD', $user->password) ? 'MATCH' : 'MISMATCH';

echo "Password check: {$result}\n";
