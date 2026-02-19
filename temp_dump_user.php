<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Model\User as ModelUser;
use App\User as AppUser;

$userModel = ModelUser::where('email', 'REDACTED_EMAIL@example.com')->orWhere('phone_number', 'REDACTED_EMAIL@example.com')->first();
$userApp = AppUser::where('email', 'REDACTED_EMAIL@example.com')->orWhere('phone_number', 'REDACTED_EMAIL@example.com')->first();

function describe($label, $user) {
    if (!$user) {
        echo $label . ": not found\n";
        return;
    }
    echo $label . ": id={$user->id}, role_id={$user->role_id}, status=" . ($user->status ?? 'null') . ", hash={$user->password}\n";
    echo $label . " hash matches? " . (Hash::check('REDACTED_PASSWORD', $user->password) ? 'YES' : 'NO') . "\n";
}

describe('App\\Model\\User', $userModel);
describe('App\\User', $userApp);
