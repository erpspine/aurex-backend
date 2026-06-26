<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use App\Mail\MembershipPaymentMail;

try {
    echo "Testing membership payment email...\n\n";
    
    // Find a member with email
    $member = Member::with('membershipPlan')->whereNotNull('email')->first();
    
    if (!$member) {
        echo "ERROR: No members with email found\n";
        exit(1);
    }
    
    echo "Member: {$member->full_name} ({$member->email})\n";
    echo "Membership Plan: " . ($member->membershipPlan->name ?? 'N/A') . "\n";
    echo "Expiry Date: " . ($member->expiry_date ? $member->expiry_date->format('F j, Y') : 'N/A') . "\n\n";
    
    // Create a test payment
    $payment = new Payment([
        'member_id' => $member->id,
        'payment_for' => 'Membership Renewal',
        'item_name' => 'Monthly Membership',
        'amount' => 50000,
        'currency' => 'TZS',
        'payment_method' => 'M-Pesa',
        'reference_number' => 'TEST-' . time(),
        'payment_date' => now(),
        'payment_status' => 'Paid',
    ]);
    
    $renewalDate = $member->expiry_date 
        ? $member->expiry_date->format('F j, Y') 
        : now()->addMonth()->format('F j, Y');
    
    echo "Sending email...\n";
    echo "APP_URL: " . config('app.mobile_app_url') . "\n\n";
    
    Mail::to($member->email)->send(new MembershipPaymentMail(
        member: $member,
        payment: $payment,
        renewalDate: $renewalDate,
        appUrl: (string) config('app.mobile_app_url'),
    ));
    
    echo "✓ Email sent successfully!\n";
    echo "Check the inbox at: {$member->email}\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
