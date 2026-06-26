<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentialsMail;

try {
    echo "Testing password reset email...\n\n";
    
    // Find a test user
    $user = User::first();
    
    if (!$user) {
        echo "ERROR: No users found in database\n";
        exit(1);
    }
    
    echo "User found: {$user->name} ({$user->email})\n";
    echo "System URL: " . config('app.frontend_url') . "\n\n";
    
    $newPassword = 'Test@123456';
    
    // Test updating password
    echo "Updating password...\n";
    $user->update([
        'password' => Hash::make($newPassword),
        'force_password_change' => true,
    ]);
    echo "✓ Password updated successfully\n\n";
    
    // Test sending email
    echo "Sending email...\n";
    echo "MAIL_MAILER: " . config('mail.default') . "\n";
    echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
    echo "MAIL_FROM: " . config('mail.from.address') . "\n\n";
    
    Mail::to($user->email)->send(new UserCredentialsMail(
        user: $user,
        plainPassword: $newPassword,
        systemUrl: (string) config('app.frontend_url'),
    ));
    
    echo "✓ Email sent successfully!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
