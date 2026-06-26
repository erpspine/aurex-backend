<?php

// Quick test to get an admin token
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admin = User::first();

if ($admin) {
    $token = $admin->createToken('test-token')->plainTextToken;
    echo "Admin: {$admin->name} ({$admin->email})\n";
    echo "User ID: {$admin->id}\n";
    echo "Token: {$token}\n\n";
    
    echo "Test with:\n";
    echo "curl -X POST https://api.aurex-performance.com/api/users/{$admin->id}/reset-password \\\n";
    echo "  -H 'Accept: application/json' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -d '{\"password\":\"NewPass@123\",\"password_confirmation\":\"NewPass@123\",\"force_password_change\":true}'\n";
} else {
    echo "No users found\n";
}
