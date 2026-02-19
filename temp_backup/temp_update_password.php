<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\User::where('email', 'sales@efficentrix.co.ke')->first();
if ($user) {
    $user->password = bcrypt('P@$$w0rd');
    $user->save();
    echo "Password updated\n";
} else {
    echo "User not found\n";
}
